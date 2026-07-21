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
  const { palette, spacing, typography, radius, elevation } = useTheme();

  return (
    <View
      style={[
        styles.card,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      <View style={styles.row}>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            lineHeight: typography.caption.lineHeight,
            fontWeight: typography.caption.fontWeight,
            letterSpacing: typography.caption.letterSpacing,
          }}
        >
          {label}
        </Text>
        {readOnly ? (
          <Ionicons name="lock-closed-outline" size={14} color={palette.textMuted} />
        ) : null}
      </View>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.body.fontSize,
          lineHeight: typography.body.lineHeight,
          fontWeight: '600',
          marginTop: spacing.xs,
        }}
      >
        {value || '—'}
      </Text>
      {hint ? (
        <Text
          style={{
            color: palette.textMuted,
            fontSize: typography.caption.fontSize,
            lineHeight: typography.caption.lineHeight,
            marginTop: spacing.xs,
          }}
        >
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
