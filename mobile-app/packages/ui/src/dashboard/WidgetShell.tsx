import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { SkeletonLoader } from '../feedback/SkeletonLoader';
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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  const cardStyle = [
    styles.card,
    elevation[2],
    {
      backgroundColor: palette.surfaceRaised,
      borderColor: palette.borderSubtle,
      borderRadius: radius.card,
      padding: spacing.md,
    },
    style,
  ];

  if (state === 'loading') {
    return (
      <View style={cardStyle} accessibilityState={{ busy: true }}>
        {title ? <SkeletonLoader height={12} width="50%" /> : null}
        <SkeletonLoader height={28} width="70%" style={{ marginTop: spacing.sm }} />
        <SkeletonLoader height={12} width="40%" style={{ marginTop: spacing.xs }} />
      </View>
    );
  }

  if (state === 'empty') {
    return (
      <View style={cardStyle}>
        {title ? (
          <Text
            style={[
              styles.meta,
              {
                color: palette.textMuted,
                fontSize: typography.overline.fontSize,
                letterSpacing: typography.overline.letterSpacing,
              },
            ]}
          >
            {title.toUpperCase()}
          </Text>
        ) : null}
        <Ionicons name="analytics-outline" size={28} color={palette.textMuted} />
        <Text
          style={[
            styles.feedback,
            {
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.sm,
            },
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
          <Text
            style={[
              styles.meta,
              {
                color: palette.textMuted,
                fontSize: typography.overline.fontSize,
                letterSpacing: typography.overline.letterSpacing,
              },
            ]}
          >
            {title.toUpperCase()}
          </Text>
        ) : null}
        <Ionicons name="alert-circle-outline" size={28} color={colors.error} />
        <Text
          style={[
            styles.feedback,
            {
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.sm,
            },
          ]}
        >
          {errorMessage}
        </Text>
        {onRetry ? (
          <Pressable onPress={onRetry} style={{ marginTop: spacing.sm }}>
            <Text
              style={{
                color: colors.primary,
                fontSize: typography.caption.fontSize,
                fontWeight: '600',
              }}
            >
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
    textTransform: 'uppercase',
    marginBottom: 4,
  },
  feedback: { textAlign: 'center' },
  skeletonLine: { height: 12, borderRadius: 4 },
  skeletonValue: { height: 28, borderRadius: 6, width: '70%' },
});
