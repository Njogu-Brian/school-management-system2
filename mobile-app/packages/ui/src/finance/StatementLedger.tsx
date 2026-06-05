import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface StatementLedgerRow {
  id: number;
  date: string;
  type: string;
  reference: string;
  description: string;
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
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  if (rows.length === 0) {
    return (
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
        No transactions for this year.
      </Text>
    );
  }

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
        },
      ]}
    >
      {rows.map((row, index) => (
        <View
          key={String(row.id)}
          style={[
            styles.row,
            {
              borderBottomColor: palette.border,
              borderBottomWidth: index < rows.length - 1 ? StyleSheet.hairlineWidth : 0,
            },
          ]}
        >
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '600' }}>
              {row.reference}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {row.date} · {row.type}
            </Text>
            {row.description ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                {row.description}
              </Text>
            ) : null}
          </View>
          <View style={{ alignItems: 'flex-end', marginLeft: spacing.sm }}>
            {row.debit > 0 ? (
              <Text style={{ color: colors.error, fontSize: fontSizes.sm }}>+{formatAmount(row.debit)}</Text>
            ) : null}
            {row.credit > 0 ? (
              <Text style={{ color: colors.success, fontSize: fontSizes.sm }}>-{formatAmount(row.credit)}</Text>
            ) : null}
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
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
  row: { flexDirection: 'row', paddingVertical: 10 },
});
