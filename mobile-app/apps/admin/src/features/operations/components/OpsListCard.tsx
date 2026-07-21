import { StatusBadge, useTheme } from '@erp/ui';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

export interface OpsListCardProps {
  title: string;
  lines?: Array<string | null | undefined>;
  badge?: { label: string; tone: 'brand' | 'success' | 'warning' | 'danger' | 'info' };
  onPress?: () => void;
  right?: React.ReactNode;
}

/** Standard V3 card row for Operations registries. */
export const OpsListCard: React.FC<OpsListCardProps> = ({ title, lines = [], badge, onPress, right }) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const visibleLines = lines.filter((l): l is string => Boolean(l && l.trim()));

  const body = (
    <View style={styles.rowWrap}>
      <View style={styles.main}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.titleSmall.fontSize,
            fontWeight: typography.titleSmall.fontWeight,
            lineHeight: typography.titleSmall.lineHeight,
          }}
        >
          {title}
        </Text>
        {visibleLines.map((line, i) => (
          <Text
            key={i}
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              fontWeight: typography.caption.fontWeight,
              lineHeight: typography.caption.lineHeight,
              marginTop: spacing.xs,
            }}
            numberOfLines={2}
          >
            {line}
          </Text>
        ))}
        {badge ? (
          <StatusBadge
            label={badge.label}
            tone={badge.tone}
            compact
            style={{ alignSelf: 'flex-start', marginTop: spacing.sm }}
          />
        ) : null}
      </View>
      {right}
    </View>
  );

  const cardStyle = ({ pressed }: { pressed: boolean }) => [
    elevation[1],
    {
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: palette.borderSubtle,
      backgroundColor: palette.surfaceRaised,
      borderRadius: radius.card,
      padding: spacing.md,
      marginBottom: spacing.sm,
      opacity: pressed ? 0.9 : 1,
    },
  ];

  if (onPress) {
    return (
      <Pressable onPress={onPress} accessibilityRole="button" style={cardStyle}>
        {body}
      </Pressable>
    );
  }
  return <View style={cardStyle({ pressed: false })}>{body}</View>;
};

const styles = StyleSheet.create({
  rowWrap: { flexDirection: 'row', alignItems: 'center' },
  main: { flex: 1 },
});
