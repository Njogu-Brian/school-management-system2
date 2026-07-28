import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
import { useTheme } from '../theme/ThemeContext';

export interface StatementLedgerRow {
  id: number;
  date: string;
  type: string;
  reference: string;
  description: string;
  votehead?: string | null;
  debit: number;
  credit: number;
  balance: number;
  invoice_id?: number | null;
  payment_id?: number | null;
  entity_type?: string | null;
}

export interface StatementLedgerProps {
  rows: StatementLedgerRow[];
  formatAmount?: (n: number) => string;
  /** When set, tappable rows open a breakdown (invoice / payment). */
  onRowPress?: (row: StatementLedgerRow) => void;
}

export const StatementLedger: React.FC<StatementLedgerProps> = ({
  rows,
  formatAmount = (n) => `KES ${n.toLocaleString('en-KE')}`,
  onRowPress,
}) => {
  const { palette, spacing, typography, radius, semantic, colors } = useTheme();

  if (rows.length === 0) {
    return (
      <EmptyState
        title="No transactions"
        message="No transactions for this period."
        icon="receipt-outline"
      />
    );
  }

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      {rows.map((row, index) => {
        const canOpen = Boolean(
          onRowPress && (row.invoice_id || row.payment_id || row.entity_type === 'invoice' || row.entity_type === 'payment'),
        );
        const Row = canOpen ? Pressable : View;
        return (
          <Row
            key={`${row.id}-${row.date}-${row.type}-${row.votehead ?? ''}-${index}`}
            onPress={canOpen ? () => onRowPress?.(row) : undefined}
            style={[
              styles.row,
              {
                borderBottomColor: palette.border,
                borderBottomWidth: index < rows.length - 1 ? StyleSheet.hairlineWidth : 0,
                paddingVertical: spacing.sm,
              },
            ]}
          >
            <View style={{ flex: 1 }}>
              <Text style={{ color: palette.textMain, fontSize: typography.body.fontSize, fontWeight: '600' }}>
                {row.reference}
              </Text>
              <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {row.date} · {row.type}
              </Text>
              {row.description ? (
                <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {row.description}
                </Text>
              ) : null}
              {row.votehead ? (
                <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {row.votehead}
                </Text>
              ) : null}
              {canOpen ? (
                <Text style={{ color: colors.primary, fontSize: typography.caption.fontSize, marginTop: 4, fontWeight: '600' }}>
                  Tap for breakdown
                </Text>
              ) : null}
            </View>
            <View style={{ alignItems: 'flex-end', marginLeft: spacing.sm }}>
              {row.debit > 0 ? (
                <Text style={{ color: semantic.danger.fg, fontSize: typography.body.fontSize }}>
                  +{formatAmount(row.debit)}
                </Text>
              ) : null}
              {row.credit > 0 ? (
                <Text style={{ color: semantic.success.fg, fontSize: typography.body.fontSize }}>
                  -{formatAmount(row.credit)}
                </Text>
              ) : null}
              <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
                Bal {formatAmount(row.balance)}
              </Text>
            </View>
          </Row>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row' },
});
