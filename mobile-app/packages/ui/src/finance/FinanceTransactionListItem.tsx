import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface FinanceTransactionListItemData {
  id: number;
  transactionType: 'bank' | 'c2b';
  reference: string | null;
  amount: number | null;
  studentName: string | null;
  status: string | null;
  transDate: string | null;
}

export interface FinanceTransactionListItemProps {
  transaction: FinanceTransactionListItemData;
  onPress?: () => void;
  formatAmount?: (n: number | null) => string;
}

export const FinanceTransactionListItem: React.FC<FinanceTransactionListItemProps> = ({
  transaction,
  onPress,
  formatAmount = (n) => (n == null ? '—' : `KES ${n.toLocaleString('en-KE')}`),
}) => {
  const { palette, spacing, typography, radius } = useTheme();
  const source = transaction.transactionType === 'c2b' ? 'M-Pesa C2B' : 'Bank';

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.card,
          padding: spacing.md,
          opacity: pressed ? 0.9 : 1,
        },
      ]}
    >
      <View style={styles.row}>
        <Ionicons
          name={transaction.transactionType === 'c2b' ? 'phone-portrait-outline' : 'business-outline'}
          size={22}
          color={palette.primary}
        />
        <View style={{ flex: 1, marginLeft: spacing.sm }}>
          <Text style={{ color: palette.textMain, fontSize: typography.titleSmall.fontSize, fontWeight: '700' }}>
            {transaction.reference ?? `#${transaction.id}`}
          </Text>
          <Text style={{ color: palette.textSub, fontSize: typography.body.fontSize, marginTop: 2 }}>
            {source}
            {transaction.studentName ? ` · ${transaction.studentName}` : ''}
          </Text>
          <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
            {transaction.transDate ?? '—'}
            {transaction.status ? ` · ${transaction.status}` : ''}
          </Text>
        </View>
        <Text style={{ color: palette.textMain, fontSize: typography.titleSmall.fontSize, fontWeight: '700' }}>
          {formatAmount(transaction.amount)}
        </Text>
        {onPress ? (
          <Ionicons name="chevron-forward" size={18} color={palette.textSub} style={{ marginLeft: spacing.xs }} />
        ) : null}
      </View>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center' },
});
