import { useCan, useFinanceTransactionDetail, useReconciliationActions } from '@erp/core';
import {
  Button,
  FinanceFieldSection,
  FinanceScreenHeader,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, Text, View } from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'TransactionDetail'>;

export const TransactionDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { transactionId, transactionType, summary } = route.params;
  const canView = useCan('finance.view');
  const { colors, palette, spacing } = useTheme();
  const detailQuery = useFinanceTransactionDetail(transactionId, transactionType, { enabled: canView });
  const { confirm, reject } = useReconciliationActions();

  const txn = detailQuery.data;
  const title = summary?.reference ?? txn?.trans_id ?? txn?.reference_number ?? `Transaction #${transactionId}`;
  const amount = txn?.trans_amount ?? txn?.amount ?? summary?.amount;

  const canAct = useMemo(() => {
    if (!txn) return false;
    const status = (txn.status ?? txn.allocation_status ?? '').toLowerCase();
    if (txn.payment_created) return false;
    if (txn.is_archived) return false;
    if (['confirmed', 'collected', 'processed', 'rejected', 'failed'].includes(status)) return false;
    return true;
  }, [txn]);

  const isConfirmed = useMemo(() => {
    if (!txn) return false;
    const status = (txn.status ?? '').toLowerCase();
    return Boolean(txn.payment_created) || ['confirmed', 'collected', 'processed'].includes(status);
  }, [txn]);

  const runConfirm = () => {
    Alert.alert('Confirm transaction', 'Create payment from this matched transaction?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Confirm',
        onPress: () => {
          void confirm
            .mutateAsync({ id: transactionId, type: transactionType })
            .then(() => {
              Alert.alert('Confirmed', 'Transaction confirmed.');
              navigation.goBack();
            })
            .catch((e: Error) => Alert.alert('Failed', e.message));
        },
      },
    ]);
  };

  const runReject = () => {
    Alert.alert('Reject transaction', 'Reject this transaction and reverse any linked payments?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Reject',
        style: 'destructive',
        onPress: () => {
          void reject
            .mutateAsync({ id: transactionId, type: transactionType })
            .then(() => {
              Alert.alert('Rejected', 'Transaction rejected.');
              navigation.goBack();
            })
            .catch((e: Error) => Alert.alert('Failed', e.message));
        },
      },
    ]);
  };

  if (!canView) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (detailQuery.isError || !txn) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <FinanceScreenHeader title={title} onBack={() => navigation.goBack()} />
        <Text style={{ color: colors.error, textAlign: 'center' }}>
          {(detailQuery.error as Error)?.message ?? 'Could not load transaction.'}
        </Text>
        <Pressable onPress={() => void detailQuery.refetch()} style={{ marginTop: spacing.sm, alignSelf: 'center' }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </ScreenContainer>
    );
  }

  const source = transactionType === 'c2b' ? 'M-Pesa C2B' : 'Bank statement';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <FinanceScreenHeader title={title} subtitle={source} onBack={() => navigation.goBack()} />

        <FinanceFieldSection
          title="Transaction"
          rows={[
            { label: 'Amount', value: formatKes(amount ?? null) },
            { label: 'Date', value: txn.trans_time ?? txn.transaction_date ?? summary?.transDate },
            { label: 'Reference', value: txn.trans_id ?? txn.reference_number ?? summary?.reference },
            { label: 'Status', value: txn.status ?? txn.allocation_status ?? summary?.status },
            { label: 'Match status', value: txn.match_status },
            { label: 'Confidence', value: txn.match_confidence != null ? `${txn.match_confidence}%` : null },
          ]}
        />

        <FinanceFieldSection
          title="Payer"
          rows={[
            { label: 'Student', value: txn.student_name },
            { label: 'Payer name', value: txn.payer_name ?? [txn.first_name, txn.last_name].filter(Boolean).join(' ') },
            { label: 'Phone', value: txn.msisdn ?? txn.phone_number },
            { label: 'Bill ref', value: txn.bill_ref_number },
            { label: 'Description', value: txn.description ?? txn.match_reason },
          ]}
        />

        <FinanceFieldSection
          title="Flags"
          rows={[
            { label: 'Payment created', value: txn.payment_created ? 'Yes' : 'No' },
            { label: 'Duplicate', value: txn.is_duplicate ? 'Yes' : 'No' },
            { label: 'Archived', value: txn.is_archived ? 'Yes' : 'No' },
            { label: 'Swimming', value: txn.is_swimming_transaction ? 'Yes' : 'No' },
            { label: 'Shared', value: txn.is_shared ? 'Yes' : 'No' },
          ]}
        />

        {isConfirmed ? (
          <View
            style={{
              marginTop: spacing.sm,
              padding: spacing.md,
              borderRadius: 10,
              backgroundColor: `${colors.success}14`,
              borderWidth: 1,
              borderColor: colors.success,
            }}
          >
            <Text style={{ color: colors.success, fontWeight: '700' }}>Confirmed</Text>
            <Text style={{ color: palette.textSecondary, fontSize: 12, marginTop: 4 }}>
              Payment has been recorded for this transaction.
            </Text>
          </View>
        ) : null}

        {canAct ? (
          <View style={{ gap: spacing.sm, marginTop: spacing.sm }}>
            <Button label="Confirm" onPress={runConfirm} loading={confirm.isPending} disabled={reject.isPending} />
            <Button
              label="Reject"
              variant="ghost"
              onPress={runReject}
              loading={reject.isPending}
              disabled={confirm.isPending}
            />
          </View>
        ) : null}

        {(confirm.error as Error | null)?.message || (reject.error as Error | null)?.message ? (
          <Text style={{ color: colors.error, marginTop: spacing.sm, fontSize: 12 }}>
            {(confirm.error as Error | null)?.message ?? (reject.error as Error | null)?.message}
          </Text>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};
