import { StudentSummaryWidgets, type StudentSummaryWidgetData } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
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
}) => {
  const { palette, colors, spacing, fontSizes } = useTheme();

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
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
        You don&apos;t have permission to view fee amounts. Status is shown on the student header.
      </Text>
    );
  }

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (isError) {
    return (
      <View style={styles.centered}>
        <Text style={{ color: colors.error }}>Could not load fee statement.</Text>
        {onRetry ? (
          <Text
            onPress={onRetry}
            style={{ color: colors.primary, marginTop: spacing.sm, fontWeight: '600' }}
          >
            Retry
          </Text>
        ) : null}
      </View>
    );
  }

  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />

      <Section title="Invoices" palette={palette} fontSizes={fontSizes} spacing={spacing}>
        {invoices.length === 0 ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>No invoices this year.</Text>
        ) : (
          invoices.slice(0, 10).map((inv) => (
            <Row
              key={inv.id}
              left={inv.reference}
              right={formatKes(inv.amount)}
              sub={formatDateLabel(inv.date)}
              palette={palette}
              fontSizes={fontSizes}
              onPress={onInvoicePress ? () => onInvoicePress(inv.id) : undefined}
            />
          ))
        )}
      </Section>

      <Section title="Payments" palette={palette} fontSizes={fontSizes} spacing={spacing}>
        {payments.length === 0 ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>No payments this year.</Text>
        ) : (
          payments.slice(0, 10).map((p) => (
            <Row
              key={p.id}
              left={p.reference}
              right={formatKes(p.amount)}
              sub={formatDateLabel(p.date)}
              palette={palette}
              fontSizes={fontSizes}
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
  fontSizes,
  spacing,
}: {
  title: string;
  children: React.ReactNode;
  palette: { textSecondary: string };
  fontSizes: { xs: number; sm: number };
  spacing: { md: number; sm: number };
}) {
  return (
    <View style={{ marginTop: spacing.md }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: fontSizes.xs,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: 0.4,
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
  fontSizes,
  onPress,
}: {
  left: string;
  right: string;
  sub: string;
  palette: { textPrimary: string; textSecondary: string; border: string };
  fontSizes: { sm: number; xs: number };
  onPress?: () => void;
}) {
  const content = (
    <>
      <View style={{ flex: 1 }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm }}>{left}</Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{sub}</Text>
      </View>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '600' }}>{right}</Text>
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
  centered: { paddingVertical: 32, alignItems: 'center' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
