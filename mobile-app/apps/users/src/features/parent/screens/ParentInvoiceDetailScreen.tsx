import { useInvoiceDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  Soft3DIcon,
  StatusBadge,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, RefreshControl, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatKes, formatShortDate } from '../utils/format';

export const ParentInvoiceDetailScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<RouteProp<ParentStackParamList, 'InvoiceDetail'>>();
  const { invoiceId, studentId } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const detailQuery = useInvoiceDetail(invoiceId);

  const invoice = detailQuery.data;

  const paidPct = useMemo(() => {
    if (!invoice) return 0;
    const total = Number(invoice.total_amount ?? 0);
    const paid = Number(invoice.paid_amount ?? 0);
    if (total <= 0) return invoice.balance <= 0 ? 100 : 0;
    return Math.max(0, Math.min(100, Math.round((paid / total) * 100)));
  }, [invoice]);

  const summaryRows = useMemo(() => {
    if (!invoice) return [];
    return [
      { label: 'Student', value: invoice.student_name ?? '—' },
      { label: 'Invoice #', value: invoice.invoice_number },
      { label: 'Term / year', value: [invoice.term_name, invoice.academic_year_name].filter(Boolean).join(' · ') || '—' },
      { label: 'Issued', value: invoice.issue_date ? formatShortDate(invoice.issue_date) : '—' },
      { label: 'Due date', value: invoice.due_date ? formatShortDate(invoice.due_date) : '—' },
    ];
  }, [invoice]);

  const itemRows = useMemo(
    () =>
      (invoice?.items ?? []).map((item) => {
        const discount = Number(item.discount_amount ?? 0);
        const gross = formatKes(item.amount);
        const net = formatKes(item.total ?? item.amount);
        const value =
          discount > 0 ? `${gross} − discount ${formatKes(discount)} = ${net}` : net;
        return {
          label: item.votehead_name,
          value,
        };
      }),
    [invoice],
  );

  if (detailQuery.isLoading && !invoice) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      scrollProps={{
        refreshControl: (
          <RefreshControl
            refreshing={detailQuery.isRefetching}
            onRefresh={() => void detailQuery.refetch()}
            colors={[colors.primary]}
          />
        ),
      }}
    >
      <AcademicScreenHeader
        title={invoice?.invoice_number ?? `Invoice #${invoiceId}`}
        subtitle="Invoice summary"
        onBack={() => navigation.goBack()}
      />
      {detailQuery.isError && !invoice ? (
        <EmptyState
          title="Could not load invoice"
          message={(detailQuery.error as Error)?.message}
          icon="receipt-outline"
          actionLabel="Retry"
          onAction={() => void detailQuery.refetch()}
        />
      ) : invoice ? (
        <>
          <SurfaceCard accent={invoice.balance > 0 ? 'warning' : 'success'}>
            <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: spacing.md, gap: spacing.sm }}>
              <Soft3DIcon name="receipt-outline" glyph="receipt" size={44} />
              <View style={{ flex: 1 }}>
                <StatusBadge label={String(invoice.status)} tone={invoice.balance > 0 ? 'warning' : 'success'} />
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {invoice.student_name ?? 'Student'}
                </Text>
              </View>
            </View>

            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Amount due</Text>
            <Text
              style={{
                color: invoice.balance > 0 ? colors.warning : colors.success,
                fontSize: 32,
                fontWeight: '800',
                marginTop: 2,
              }}
            >
              {formatKes(invoice.balance)}
            </Text>

            <View style={{ flexDirection: 'row', marginTop: spacing.md, gap: spacing.md }}>
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>Total</Text>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {formatKes(invoice.total_amount)}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>Paid</Text>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {formatKes(invoice.paid_amount)}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>Progress</Text>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{paidPct}%</Text>
              </View>
            </View>

            <View
              style={{
                height: 8,
                borderRadius: 999,
                backgroundColor: palette.borderSubtle ?? '#E5E7EB',
                marginTop: spacing.sm,
                overflow: 'hidden',
              }}
            >
              <View
                style={{
                  width: `${paidPct}%`,
                  height: '100%',
                  backgroundColor: invoice.balance > 0 ? colors.warning : colors.success,
                }}
              />
            </View>

            {invoice.due_date ? (
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.sm }}>
                Due {formatShortDate(invoice.due_date)}
                {invoice.term_name ? ` · ${invoice.term_name}` : ''}
              </Text>
            ) : null}
          </SurfaceCard>

          <FinanceFieldSection title="Details" rows={summaryRows} />
          {itemRows.length > 0 ? (
            <View style={{ marginTop: spacing.md }}>
              <FinanceFieldSection title="Line items" rows={itemRows} />
            </View>
          ) : null}
          {invoice.balance > 0 ? (
            <View style={{ marginTop: spacing.lg, gap: spacing.sm }}>
              <Button
                label="Pay with M-Pesa"
                onPress={() =>
                  navigation.navigate('MpesaPrompt', {
                    studentId: invoice.student_id || studentId,
                    amount: invoice.balance,
                  })
                }
              />
              <Button
                label="Pay from wallet"
                variant="secondary"
                onPress={() =>
                  navigation.navigate('WalletHome', {
                    payInvoiceId: invoice.id,
                    studentId: invoice.student_id || studentId,
                  })
                }
              />
            </View>
          ) : null}
        </>
      ) : (
        <Text style={{ color: palette.textSecondary }}>Invoice not found.</Text>
      )}
    </ScreenContainer>
  );
};
