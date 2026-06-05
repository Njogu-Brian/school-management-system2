import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

const STATUS_OPTIONS = [
  { value: '', label: 'All' },
  { value: 'draft', label: 'Draft' },
  { value: 'marking', label: 'Marking' },
  { value: 'moderation', label: 'Moderation' },
  { value: 'published', label: 'Published' },
];

export interface ExamFiltersProps {
  status: string;
  onStatusChange: (status: string) => void;
}

export const ExamFilters: React.FC<ExamFiltersProps> = ({ status, onStatusChange }) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={{ gap: spacing.xs, marginBottom: spacing.sm }}
    >
      {STATUS_OPTIONS.map((opt) => {
        const active = status === opt.value;
        return (
          <Pressable
            key={opt.label}
            onPress={() => onStatusChange(opt.value)}
            style={[
              styles.chip,
              {
                backgroundColor: active ? colors.primary : palette.surface,
                borderColor: active ? colors.primary : palette.border,
                borderRadius: radius.full,
              },
            ]}
          >
            <Text
              style={{
                color: active ? colors.white : palette.textSecondary,
                fontSize: fontSizes.xs,
                fontWeight: '700',
              }}
            >
              {opt.label}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderWidth: StyleSheet.hairlineWidth,
  },
});
