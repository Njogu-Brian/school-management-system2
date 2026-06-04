import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { WidgetSeverity } from './types';

export interface AlertCardProps {
  title: string;
  message: string;
  severity?: WidgetSeverity;
  timestamp?: string;
  onPress?: () => void;
}

const SEVERITY_ICON: Record<WidgetSeverity, keyof typeof Ionicons.glyphMap> = {
  info: 'information-circle-outline',
  success: 'checkmark-circle-outline',
  warning: 'warning-outline',
  error: 'alert-circle-outline',
};

export const AlertCard: React.FC<AlertCardProps> = ({
  title,
  message,
  severity = 'warning',
  timestamp,
  onPress,
}) => {
  const { palette, colors, fontSizes, spacing, radius } = useTheme();

  const accent =
    severity === 'error'
      ? colors.error
      : severity === 'warning'
        ? colors.warning
        : severity === 'success'
          ? colors.success
          : colors.info;

  const content = (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderLeftColor: accent,
          borderRadius: radius.md,
          padding: spacing.md,
        },
      ]}
    >
      <View style={styles.row}>
        <Ionicons name={SEVERITY_ICON[severity]} size={22} color={accent} />
        <View style={styles.textCol}>
          <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.sm }]}>
            {title}
          </Text>
          <Text style={[styles.message, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
            {message}
          </Text>
          {timestamp ? (
            <Text style={[styles.time, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
              {timestamp}
            </Text>
          ) : null}
        </View>
        {onPress ? <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} /> : null}
      </View>
    </View>
  );

  if (onPress) {
    return (
      <Pressable onPress={onPress} accessibilityRole="button">
        {content}
      </Pressable>
    );
  }

  return content;
};

const styles = StyleSheet.create({
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    borderLeftWidth: 3,
    marginBottom: 8,
  },
  row: { flexDirection: 'row', alignItems: 'flex-start' },
  textCol: { flex: 1, marginLeft: 10 },
  title: { fontWeight: '700' },
  message: { marginTop: 2, lineHeight: 18 },
  time: { marginTop: 4, fontWeight: '500' },
});
