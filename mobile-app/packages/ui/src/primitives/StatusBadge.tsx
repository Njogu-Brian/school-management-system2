import React from 'react';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { SemanticTone } from '../theme/tokens';

export interface StatusBadgeProps {
  label: string;
  tone?: SemanticTone;
  /** Override semantic tone with custom colors */
  backgroundColor?: string;
  textColor?: string;
  compact?: boolean;
  style?: ViewStyle;
}

/** Unified status badge — semantic tones with pill shape. */
export const StatusBadge: React.FC<StatusBadgeProps> = ({
  label,
  tone = 'brand',
  backgroundColor,
  textColor,
  compact = false,
  style,
}) => {
  const { semantic, typography, radius, spacing, isDark } = useTheme();
  const role = semantic[tone];

  const bg = backgroundColor ?? (isDark ? `${role.fg}22` : role.bg);
  const fg = textColor ?? role.fg;

  return (
    <View
      style={[
        styles.badge,
        compact && styles.compact,
        {
          backgroundColor: bg,
          borderRadius: radius.sm,
          paddingHorizontal: compact ? spacing.sm : spacing.sm + 2,
          paddingVertical: compact ? 2 : spacing.xs - 1,
        },
        style,
      ]}
    >
      <Text
        style={{
          color: fg,
          fontSize: compact ? typography.overline.fontSize : typography.caption.fontSize,
          fontWeight: '700',
          letterSpacing: 0.3,
          textTransform: 'uppercase',
        }}
      >
        {label}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: { alignSelf: 'flex-start' },
  compact: {},
});
