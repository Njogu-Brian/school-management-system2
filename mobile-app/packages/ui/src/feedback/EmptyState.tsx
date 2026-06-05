import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface EmptyStateProps {
  title: string;
  message?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  actionLabel?: string;
  onAction?: () => void;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  title,
  message,
  icon = 'file-tray-outline',
  actionLabel,
  onAction,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={[styles.wrap, { padding: spacing.lg }]}>
      <View
        style={[
          styles.iconCircle,
          { backgroundColor: palette.accent, borderRadius: radius.full },
        ]}
      >
        <Ionicons name={icon} size={32} color={colors.primary} />
      </View>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: fontSizes.md,
          fontWeight: '700',
          textAlign: 'center',
          marginTop: spacing.md,
        }}
      >
        {title}
      </Text>
      {message ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: fontSizes.sm,
            textAlign: 'center',
            marginTop: spacing.xs,
            lineHeight: 20,
          }}
        >
          {message}
        </Text>
      ) : null}
      {actionLabel && onAction ? (
        <Pressable
          onPress={onAction}
          style={[
            styles.action,
            {
              marginTop: spacing.md,
              backgroundColor: `${colors.primary}14`,
              borderRadius: radius.md,
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.sm,
            },
          ]}
        >
          <Text style={{ color: colors.primary, fontWeight: '600', fontSize: fontSizes.sm }}>
            {actionLabel}
          </Text>
        </Pressable>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { alignItems: 'center', justifyContent: 'center' },
  iconCircle: {
    width: 64,
    height: 64,
    alignItems: 'center',
    justifyContent: 'center',
  },
  action: {},
});
