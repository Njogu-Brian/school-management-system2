import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { WidgetDisplayState } from './types';

export interface WidgetShellProps {
  state: WidgetDisplayState;
  /** Shown in loading/error/empty chrome when provided. */
  title?: string;
  emptyMessage?: string;
  errorMessage?: string;
  onRetry?: () => void;
  children?: React.ReactNode;
  style?: object;
}

/**
 * Uniform widget chrome: loading skeleton, empty, error + retry, or success children.
 * Data fetching lives in the app layer; this component is presentational only.
 */
export const WidgetShell: React.FC<WidgetShellProps> = ({
  state,
  title,
  emptyMessage = 'No data for this period',
  errorMessage = 'Could not load this metric',
  onRetry,
  children,
  style,
}) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  const cardStyle = [
    styles.card,
    {
      backgroundColor: palette.surface,
      borderColor: palette.border,
      borderRadius: radius.lg,
      padding: spacing.md,
    },
    shadows.sm,
    style,
  ];

  if (state === 'loading') {
    return (
      <View style={cardStyle} accessibilityState={{ busy: true }}>
        {title ? (
          <View style={[styles.skeletonLine, { width: '50%', backgroundColor: palette.border }]} />
        ) : null}
        <View
          style={[
            styles.skeletonValue,
            { backgroundColor: palette.border, marginTop: spacing.sm },
          ]}
        />
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} />
      </View>
    );
  }

  if (state === 'empty') {
    return (
      <View style={cardStyle}>
        {title ? (
          <Text style={[styles.meta, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
            {title}
          </Text>
        ) : null}
        <Ionicons name="analytics-outline" size={28} color={palette.textSecondary} />
        <Text
          style={[
            styles.feedback,
            { color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: spacing.sm },
          ]}
        >
          {emptyMessage}
        </Text>
      </View>
    );
  }

  if (state === 'error') {
    return (
      <View style={cardStyle}>
        {title ? (
          <Text style={[styles.meta, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
            {title}
          </Text>
        ) : null}
        <Ionicons name="alert-circle-outline" size={28} color={colors.error} />
        <Text
          style={[
            styles.feedback,
            { color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: spacing.sm },
          ]}
        >
          {errorMessage}
        </Text>
        {onRetry ? (
          <Pressable onPress={onRetry} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.primary, fontSize: fontSizes.sm, fontWeight: '600' }}>
              Retry
            </Text>
          </Pressable>
        ) : null}
      </View>
    );
  }

  return <View style={cardStyle}>{children}</View>;
};

const styles = StyleSheet.create({
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    minHeight: 112,
    justifyContent: 'center',
  },
  meta: {
    fontWeight: '600',
    letterSpacing: 0.3,
    textTransform: 'uppercase',
    marginBottom: 4,
  },
  feedback: { textAlign: 'center' },
  skeletonLine: { height: 12, borderRadius: 4 },
  skeletonValue: { height: 28, borderRadius: 6, width: '70%' },
});
