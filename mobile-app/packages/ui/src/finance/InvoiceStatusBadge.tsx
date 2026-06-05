import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export function invoiceStatusLabel(status: string): string {
  switch (status) {
    case 'issued':
      return 'Issued';
    case 'partially_paid':
      return 'Partial';
    case 'paid':
      return 'Paid';
    case 'overdue':
      return 'Overdue';
    case 'reversed':
      return 'Reversed';
    default:
      return status;
  }
}

export interface InvoiceStatusBadgeProps {
  status: string;
}

export const InvoiceStatusBadge: React.FC<InvoiceStatusBadgeProps> = ({ status }) => {
  const { colors, palette, fontSizes, radius, spacing } = useTheme();
  const label = invoiceStatusLabel(status);

  let bg = `${palette.textSecondary}22`;
  let fg = palette.textSecondary;
  if (status === 'paid') {
    bg = `${colors.success}22`;
    fg = colors.success;
  } else if (status === 'partially_paid' || status === 'issued') {
    bg = `${colors.warning}22`;
    fg = colors.warning;
  } else if (status === 'overdue') {
    bg = `${colors.error}22`;
    fg = colors.error;
  }

  return (
    <View style={[styles.badge, { backgroundColor: bg, borderRadius: radius.full, paddingHorizontal: spacing.sm, paddingVertical: 2 }]}>
      <Text style={{ color: fg, fontSize: fontSizes.xs, fontWeight: '700' }}>{label}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: { alignSelf: 'flex-start' },
});
