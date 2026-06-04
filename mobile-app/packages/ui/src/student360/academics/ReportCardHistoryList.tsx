import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import type { ReportCardHistoryItemData } from './types';

export interface ReportCardHistoryListProps {
  title?: string;
  items: ReportCardHistoryItemData[];
  onPressItem?: (id: number) => void;
  emptyMessage?: string;
}

export const ReportCardHistoryList: React.FC<ReportCardHistoryListProps> = ({
  title = 'Report cards',
  items,
  onPressItem,
  emptyMessage = 'No report cards yet.',
}) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <View style={{ marginTop: spacing.lg }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: fontSizes.xs,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: 0.4,
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      {items.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>{emptyMessage}</Text>
      ) : (
        items.map((item) => (
          <Pressable
            key={item.id}
            disabled={!onPressItem}
            onPress={() => onPressItem?.(item.id)}
            style={[
              styles.row,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              },
              shadows.sm,
            ]}
          >
            <View style={styles.iconWrap}>
              <Ionicons name="document-text-outline" size={20} color={colors.primary} />
            </View>
            <View style={styles.body}>
              <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '600' }}>
                {item.title}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                {item.subtitle}
              </Text>
              {item.generatedAtLabel ? (
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                  {item.generatedAtLabel}
                </Text>
              ) : null}
            </View>
            <View style={styles.trailing}>
              <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '700' }}>
                {item.percentageLabel}
              </Text>
              <Text
                style={{
                  color: item.status === 'published' ? colors.success : palette.textSecondary,
                  fontSize: fontSizes.xs,
                  marginTop: 2,
                  textTransform: 'capitalize',
                }}
              >
                {item.status}
              </Text>
            </View>
            {onPressItem ? (
              <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} style={{ marginLeft: 4 }} />
            ) : null}
          </Pressable>
        ))
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  iconWrap: { marginRight: 10 },
  body: { flex: 1 },
  trailing: { alignItems: 'flex-end' },
});
