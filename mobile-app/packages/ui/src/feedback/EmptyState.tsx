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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View style={[styles.wrap, { padding: spacing.lg }]}>
      <View
        style={[
          styles.iconCircle,
          elevation[1],
          {
            backgroundColor: palette.surfaceMuted,
            borderRadius: radius['2xl'],
          },
        ]}
      >
        <Ionicons name={icon} size={36} color={colors.primary} />
      </View>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.title.fontSize,
          fontWeight: typography.title.fontWeight,
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
            fontSize: typography.body.fontSize,
            textAlign: 'center',
            marginTop: spacing.xs,
            lineHeight: typography.body.lineHeight,
            maxWidth: 280,
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
            elevation[1],
            {
              marginTop: spacing.md,
              backgroundColor: palette.surfaceRaised,
              borderColor: colors.primary,
              borderRadius: radius.control,
              paddingHorizontal: spacing.lg,
              paddingVertical: spacing.sm,
              borderWidth: 1,
            },
          ]}
        >
          <Text
            style={{
              color: colors.primary,
              fontWeight: '600',
              fontSize: typography.body.fontSize,
            }}
          >
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
    width: 80,
    height: 80,
    alignItems: 'center',
    justifyContent: 'center',
  },
  action: {},
});
