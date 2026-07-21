import { useApproveRequisition, useCan, useRejectRequisition, useRequisition } from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { capitalizeStatus, formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'RequisitionDetail'>;

export const RequisitionDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { requisitionId } = route.params;
  const canView = useCan('operations.view');
  const { colors, palette, spacing, typography } = useTheme();
  const query = useRequisition(requisitionId, { enabled: canView });
  const approveMutation = useApproveRequisition();
  const rejectMutation = useRejectRequisition();
  const [approvedQty, setApprovedQty] = useState<Record<number, string>>({});
  const [rejectReason, setRejectReason] = useState('');

  const req = query.data;

  useEffect(() => {
    if (req?.items) {
      const map: Record<number, string> = {};
      req.items.forEach((item) => {
        map[item.id] = String(item.quantity_approved ?? item.quantity_requested);
      });
      setApprovedQty(map);
    }
  }, [req?.items]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (!req) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error }}>Requisition not found.</Text>
      </ScreenContainer>
    );
  }

  const onApprove = () => {
    confirmAction('Approve', `Approve ${req.requisition_number}?`, 'Approve', async () => {
      const items = (req.items ?? []).map((item) => ({
        id: item.id,
        quantity_approved: parseFloat(approvedQty[item.id] ?? String(item.quantity_requested)) || 0,
      }));
      try {
        await approveMutation.mutateAsync({ id: req.id, items });
        showSuccess('Approved', undefined, () => navigation.goBack());
      } catch (err) {
        showError('Approve failed', (err as Error).message);
      }
    });
  };

  const onReject = () => {
    if (!rejectReason.trim()) {
      showError('Validation', 'Rejection reason is required.');
      return;
    }
    confirmAction('Reject', `Reject ${req.requisition_number}?`, 'Reject', async () => {
      try {
        await rejectMutation.mutateAsync({ id: req.id, rejection_reason: rejectReason.trim() });
        showSuccess('Rejected', undefined, () => navigation.goBack());
      } catch (err) {
        showError('Reject failed', (err as Error).message);
      }
    }, true);
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title={req.requisition_number} onBack={() => navigation.goBack()} />
        <FinanceFieldSection
          title="Summary"
          rows={[
            { label: 'Requester', value: req.requested_by ?? '—' },
            { label: 'Status', value: capitalizeStatus(req.status) },
            { label: 'Type', value: capitalizeStatus(req.type) },
            { label: 'Requested', value: formatDateTimeLabel(req.requested_at) },
            { label: 'Purpose', value: req.purpose ?? '—' },
          ]}
        />

        <Text style={{ fontWeight: '700', color: palette.textPrimary, marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Line items
        </Text>
        {(req.items ?? []).map((item) => (
          <View key={item.id} style={[styles.line, { borderColor: palette.border }]}>
            <Text style={{ fontWeight: '600', color: palette.textPrimary }}>{item.item_name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
              Requested: {item.quantity_requested} {item.unit ?? ''}
            </Text>
            {req.can_approve ? (
              <TextField
                label="Approved qty"
                value={approvedQty[item.id] ?? ''}
                onChangeText={(v) => setApprovedQty((prev) => ({ ...prev, [item.id]: v }))}
                keyboardType="numeric"
              />
            ) : (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                Approved: {item.quantity_approved ?? '—'} · Issued: {item.quantity_issued ?? '—'}
              </Text>
            )}
          </View>
        ))}

        {req.can_approve ? (
          <View style={{ marginTop: spacing.lg, gap: spacing.sm }}>
            <Button label="Approve" onPress={onApprove} loading={approveMutation.isPending} />
            <TextField label="Rejection reason" value={rejectReason} onChangeText={setRejectReason} multiline />
            <Button label="Reject" variant="ghost" onPress={onReject} loading={rejectMutation.isPending} />
          </View>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  line: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
