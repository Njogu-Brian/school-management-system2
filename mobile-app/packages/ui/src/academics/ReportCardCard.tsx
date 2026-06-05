import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ReportCardCardData } from './types';

export interface ReportCardCardProps {
  card: ReportCardCardData;
}

export const ReportCardCard: React.FC<ReportCardCardProps> = ({ card }) => {
  const { palette, spacing, fontSizes, radius, shadows } = useTheme();
  const isPublished = card.status === 'published';

  const body = (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
        },
        shadows.sm,
      ]}
    >
      <View style={{ flex: 1 }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700' }}>
          {card.termLabel}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}>
          {card.overallGrade ?? '—'}
          {card.overallPercentage != null && card.overallPercentage > 0
            ? ` · ${card.overallPercentage}%`
            : ''}
        </Text>
        <View
          style={[
            styles.badge,
            {
              backgroundColor: isPublished ? '#DCFCE7' : '#FEF3C7',
              borderRadius: radius.sm,
              marginTop: spacing.xs,
            },
          ]}
        >
          <Text
            style={{
              color: isPublished ? '#15803D' : '#B45309',
              fontSize: fontSizes.xs,
              fontWeight: '700',
            }}
          >
            {isPublished ? 'Published' : 'Draft'}
          </Text>
        </View>
      </View>
      <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} />
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
