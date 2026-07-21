import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { InvoiceStatusBadge } from './InvoiceStatusBadge';

export interface InvoiceListItemData {
  id: number;
  invoiceNumber: string;
  studentName: string | null;
  studentAdmissionNumber?: string | null;
  totalAmount: number;
  balance: number;
  status: string;
  issueDate: string;
}

export interface InvoiceListItemProps {
  invoice: InvoiceListItemData;
  onPress?: () => void;
  formatAmount?: (n: number) => string;
}

export const InvoiceListItem: React.FC<InvoiceListItemProps> = ({
  invoice,
  onPress,
  formatAmount = (n) => `KES ${n.toLocaleString('en-KE')}`,
}) => {
  const { palette, spacing, typography, radius, semantic } = useTheme();

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
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textMain, fontSize: typography.titleSmall.fontSize, fontWeight: '700' }}>
            {invoice.invoiceNumber}
          </Text>
          <Text style={{ color: palette.textSub, fontSize: typography.body.fontSize, marginTop: 2 }}>
            {invoice.studentName ?? '—'}
            {invoice.studentAdmissionNumber ? ` · ${invoice.studentAdmissionNumber}` : ''}
          </Text>
          <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
            {invoice.issueDate}
          </Text>
        </View>
        <View style={{ alignItems: 'flex-end' }}>
          <Text style={{ color: palette.textMain, fontSize: typography.titleSmall.fontSize, fontWeight: '700' }}>
            {formatAmount(invoice.totalAmount)}
          </Text>
          {invoice.balance > 0 ? (
            <Text style={{ color: semantic.danger.fg, fontSize: typography.caption.fontSize, marginTop: 2 }}>
              Bal {formatAmount(invoice.balance)}
            </Text>
          ) : null}
          <View style={{ marginTop: spacing.xs }}>
            <InvoiceStatusBadge status={invoice.status} />
          </View>
        </View>
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
