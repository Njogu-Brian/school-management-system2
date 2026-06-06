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
  const { palette, colors, typography, spacing, radius, elevation } = useTheme();

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
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderLeftColor: accent,
          borderRadius: radius.control,
          padding: spacing.md,
        },
      ]}
    >
      <View style={styles.row}>
        <View style={[styles.iconWrap, { backgroundColor: `${accent}14`, borderRadius: radius.sm }]}>
          <Ionicons name={SEVERITY_ICON[severity]} size={20} color={accent} />
        </View>
        <View style={styles.textCol}>
          <Text
            style={[
              styles.title,
              { color: palette.textPrimary, fontSize: typography.body.fontSize, fontWeight: '600' },
            ]}
          >
            {title}
          </Text>
          <Text
            style={[
              styles.message,
              { color: palette.textSecondary, fontSize: typography.caption.fontSize },
            ]}
          >
            {message}
          </Text>
          {timestamp ? (
            <Text
              style={[
                styles.time,
                { color: palette.textMuted, fontSize: typography.caption.fontSize },
              ]}
            >
              {timestamp}
            </Text>
          ) : null}
        </View>
        {onPress ? <Ionicons name="chevron-forward" size={18} color={palette.textMuted} /> : null}
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
  iconWrap: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  textCol: { flex: 1 },
  title: {},
  message: { marginTop: 2, lineHeight: 18 },
  time: { marginTop: 4, fontWeight: '500' },
});
