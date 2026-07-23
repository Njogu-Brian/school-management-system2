import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
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
}

export interface StatementLedgerProps {
  rows: StatementLedgerRow[];
  formatAmount?: (n: number) => string;
}

export const StatementLedger: React.FC<StatementLedgerProps> = ({
  rows,
  formatAmount = (n) => `KES ${n.toLocaleString('en-KE')}`,
}) => {
  const { palette, spacing, typography, radius, semantic } = useTheme();

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
      {rows.map((row, index) => (
        <View
          key={`${row.id}-${row.date}-${row.type}-${row.votehead ?? ''}-${index}`}
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
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row' },
});
