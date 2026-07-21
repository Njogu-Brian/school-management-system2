import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AccentIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { ReportCardCardData } from './types';

export interface ReportCardCardProps {
  card: ReportCardCardData;
}

export const ReportCardCard: React.FC<ReportCardCardProps> = ({ card }) => {
  const { palette, semantic, spacing, typography, radius, elevation } = useTheme();
  const isPublished = card.status === 'published';

  const body = (
    <View
      style={[
        styles.row,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      <AccentIcon
        name="ribbon-outline"
        tone={isPublished ? 'emerald' : 'amber'}
        size={44}
        iconSize={20}
        style={{ marginRight: spacing.sm }}
      />
      <View style={{ flex: 1 }}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.titleSmall.fontSize,
            fontWeight: '700',
          }}
        >
          {card.termLabel}
        </Text>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.body.fontSize,
            marginTop: 2,
          }}
        >
          {card.overallGrade ?? '—'}
          {card.overallPercentage != null && card.overallPercentage > 0
            ? ` · ${card.overallPercentage}%`
            : ''}
        </Text>
        <View
          style={[
            styles.badge,
            {
              backgroundColor: isPublished ? semantic.success.bg : semantic.warning.bg,
              borderRadius: radius.sm,
              marginTop: spacing.xs,
            },
          ]}
        >
          <Text
            style={{
              color: isPublished ? semantic.success.fg : semantic.warning.fg,
              fontSize: typography.caption.fontSize,
              fontWeight: '700',
            }}
          >
            {isPublished ? 'Published' : 'Draft'}
          </Text>
        </View>
      </View>
      <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
    </View>
  );

  if (card.onPress) {
    return (
      <Pressable
        onPress={card.onPress}
        accessibilityRole="button"
        style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1, marginBottom: spacing.sm }]}
      >
        {body}
      </Pressable>
    );
  }

  return <View style={{ marginBottom: spacing.sm }}>{body}</View>;
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  badge: { alignSelf: 'flex-start', paddingHorizontal: 8, paddingVertical: 2 },
});
