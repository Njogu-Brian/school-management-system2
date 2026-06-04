import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { ApprovalPriorityBadge } from './ApprovalPriorityBadge';
import { ApprovalStatusBadge } from './ApprovalStatusBadge';
import type { ApprovalCardData } from './types';

export interface ApprovalCardProps {
  item: ApprovalCardData;
}

export const ApprovalCard: React.FC<ApprovalCardProps> = ({ item }) => {
  const { palette, spacing, fontSizes, radius, shadows } = useTheme();

  const content = (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
        },
        shadows.sm,
      ]}
    >
      <View style={styles.topRow}>
        <View style={styles.titleBlock}>
          <Text
            style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.md }]}
            numberOfLines={1}
          >
            {item.title}
          </Text>
          {item.sourceLabel ? (
            <Text
              style={[
                styles.source,
                { color: palette.textSecondary, fontSize: fontSizes.xs },
              ]}
            >
              {item.sourceLabel}
            </Text>
          ) : null}
        </View>
        <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} />
      </View>

      <Text
        style={[styles.subtitle, { color: palette.textSecondary, fontSize: fontSizes.sm }]}
        numberOfLines={2}
      >
        {item.subtitle}
      </Text>

      <View style={[styles.badges, { marginTop: spacing.sm, gap: spacing.xs }]}>
        <ApprovalStatusBadge status={item.status} compact />
        <ApprovalPriorityBadge priority={item.priority} compact />
      </View>

      {item.requestedAtLabel ? (
        <Text
          style={[
            styles.meta,
            { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.xs },
          ]}
        >
          {item.requestedAtLabel}
        </Text>
      ) : null}
    </View>
  );

  if (item.onPress) {
    return (
      <Pressable
        onPress={item.onPress}
        accessibilityRole="button"
        style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1 }]}
      >
        {content}
      </Pressable>
    );
  }

  return content;
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, marginBottom: 10 },
  topRow: { flexDirection: 'row', alignItems: 'flex-start' },
  titleBlock: { flex: 1, marginRight: 8 },
  title: { fontWeight: '700' },
  source: { marginTop: 2, fontWeight: '500' },
  subtitle: { marginTop: 6 },
  badges: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
  meta: { fontWeight: '500' },
});
