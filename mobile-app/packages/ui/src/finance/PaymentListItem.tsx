import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface PaymentListItemData {
  id: number;
  receiptNumber: string;
  studentName: string | null;
  amount: number;
  paymentMethod: string;
  paymentDate: string;
  status: string;
}

export interface PaymentListItemProps {
  payment: PaymentListItemData;
  onPress?: () => void;
  formatAmount?: (n: number) => string;
  methodLabel?: (m: string) => string;
}

export const PaymentListItem: React.FC<PaymentListItemProps> = ({
  payment,
  onPress,
  formatAmount = (n) => `KES ${n.toLocaleString('en-KE')}`,
  methodLabel = (m) => m.replace(/_/g, ' '),
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();
  const reversed = payment.status === 'reversed';

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
          opacity: pressed ? 0.9 : 1,
        },
      ]}
    >
      <View style={styles.row}>
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700' }}>
            {payment.receiptNumber}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}>
            {payment.studentName ?? '—'}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
            {methodLabel(payment.paymentMethod)} · {payment.paymentDate}
          </Text>
        </View>
        <View style={{ alignItems: 'flex-end' }}>
          <Text style={{ color: reversed ? palette.textSecondary : colors.success, fontSize: fontSizes.md, fontWeight: '700' }}>
            {formatAmount(payment.amount)}
          </Text>
          {reversed ? (
            <Text style={{ color: colors.error, fontSize: fontSizes.xs, marginTop: 2 }}>Reversed</Text>
          ) : null}
        </View>
        {onPress ? (
          <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} style={{ marginLeft: spacing.xs }} />
        ) : null}
      </View>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center' },
});
