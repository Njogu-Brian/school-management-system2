import { EmptyState, StudentSummaryWidgets, type StudentSummaryWidgetData, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { formatDateLabel, formatKes } from '../utils/formatters';

export interface FeesTabProps {
  canViewFees: boolean;
  isLoading: boolean;
  isError: boolean;
  onRetry?: () => void;
  closingBalance?: number;
  totalInvoiced?: number;
  totalPaid?: number;
  invoices: Array<{ id: number; date: string; reference: string; amount: number }>;
  payments: Array<{ id: number; date: string; reference: string; amount: number }>;
  onInvoicePress?: (invoiceId: number) => void;
  onPaymentPress?: (paymentId: number) => void;
}

export const FeesTab: React.FC<FeesTabProps> = ({
  canViewFees,
  isLoading,
  isError,
  onRetry,
  closingBalance,
  totalInvoiced,
  totalPaid,
  invoices,
  payments,
  onInvoicePress,
  onPaymentPress,
}) => {
  const { palette, colors, spacing, typography } = useTheme();

  const widgets = useMemo(
    (): StudentSummaryWidgetData[] => [
      { id: 'bal', label: 'Balance', value: formatKes(closingBalance), icon: 'wallet-outline' },
      { id: 'inv', label: 'Invoiced', value: formatKes(totalInvoiced), icon: 'receipt-outline' },
      { id: 'paid', label: 'Paid', value: formatKes(totalPaid), icon: 'cash-outline' },
    ],
    [closingBalance, totalInvoiced, totalPaid],
  );

  if (!canViewFees) {
    return (
      <EmptyState
        title="Fees restricted"
        message="You don't have permission to view fee amounts. Status is shown on the student header."
        icon="lock-closed-outline"
      />
    );
  }

  if (isLoading) {
    return (
      <View style={[styles.centered, { paddingVertical: spacing.xl }]}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (isError) {
    return (
      <EmptyState
        title="Could not load fees"
        message="Unable to load the fee statement right now."
        icon="alert-circle-outline"
        actionLabel={onRetry ? 'Retry' : undefined}
        onAction={onRetry}
      />
    );
  }

  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />

      <Section title="Invoices" palette={palette} typography={typography} spacing={spacing}>
        {invoices.length === 0 ? (
          <EmptyState
            title="No invoices"
            message="No invoices on file for this student."
            icon="receipt-outline"
          />
        ) : (
          invoices.slice(0, 10).map((inv) => (
            <Row
              key={inv.id}
              left={inv.reference}
              right={formatKes(inv.amount)}
              sub={formatDateLabel(inv.date)}
              palette={palette}
              typography={typography}
              onPress={onInvoicePress ? () => onInvoicePress(inv.id) : undefined}
            />
          ))
        )}
      </Section>

      <Section title="Payments" palette={palette} typography={typography} spacing={spacing}>
        {payments.length === 0 ? (
          <EmptyState
            title="No payments"
            message="No payments on file for this student."
            icon="cash-outline"
          />
        ) : (
          payments.slice(0, 10).map((p) => (
            <Row
              key={p.id}
              left={p.reference}
              right={formatKes(p.amount)}
              sub={formatDateLabel(p.date)}
              palette={palette}
              typography={typography}
              onPress={onPaymentPress ? () => onPaymentPress(p.id) : undefined}
            />
          ))
        )}
      </Section>
    </View>
  );
};

function Section({
  title,
  children,
  palette,
  typography,
  spacing,
}: {
  title: string;
  children: React.ReactNode;
  palette: { textSub: string };
  typography: {
    overline: { fontSize: number; letterSpacing: number };
  };
  spacing: { md: number; sm: number };
}) {
  return (
    <View style={{ marginTop: spacing.md }}>
      <Text
        style={{
          color: palette.textSub,
          fontSize: typography.overline.fontSize,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: typography.overline.letterSpacing,
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      {children}
    </View>
  );
}

function Row({
  left,
  right,
  sub,
  palette,
  typography,
  onPress,
}: {
  left: string;
  right: string;
  sub: string;
  palette: { textMain: string; textSub: string; border: string };
  typography: {
    body: { fontSize: number };
    caption: { fontSize: number };
  };
  onPress?: () => void;
}) {
  const content = (
    <>
      <View style={{ flex: 1 }}>
        <Text style={{ color: palette.textMain, fontSize: typography.body.fontSize }}>{left}</Text>
        <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize }}>{sub}</Text>
      </View>
      <Text style={{ color: palette.textMain, fontSize: typography.body.fontSize, fontWeight: '600' }}>
        {right}
      </Text>
    </>
  );
  if (onPress) {
    return (
      <Pressable onPress={onPress} style={[styles.row, { borderBottomColor: palette.border }]}>
        {content}
      </Pressable>
    );
  }
  return <View style={[styles.row, { borderBottomColor: palette.border }]}>{content}</View>;
}

const styles = StyleSheet.create({
  centered: { alignItems: 'center' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
