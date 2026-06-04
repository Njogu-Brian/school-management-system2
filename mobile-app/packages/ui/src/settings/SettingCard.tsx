import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { SettingCardData } from './types';

export interface SettingCardProps extends SettingCardData {
  readOnly?: boolean;
}

export const SettingCard: React.FC<SettingCardProps> = ({
  label,
  value,
  hint,
  readOnly = true,
}) => {
  const { palette, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.md,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
        shadows.sm,
      ]}
    >
      <View style={styles.row}>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
          {label}
        </Text>
        {readOnly ? (
          <Ionicons name="lock-closed-outline" size={14} color={palette.textSecondary} />
        ) : null}
      </View>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: fontSizes.md,
          fontWeight: '600',
          marginTop: 4,
        }}
      >
        {value || '—'}
      </Text>
      {hint ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
          {hint}
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
