import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import type { AssessmentFilterOption, SubjectFilterOption } from './types';

export interface AssessmentFiltersProps {
  categories: AssessmentFilterOption[];
  selectedCategory: string;
  onCategoryChange: (id: string) => void;
  subjects?: SubjectFilterOption[];
  selectedSubjectId?: number | null;
  onSubjectChange?: (id: number | null) => void;
}

export const AssessmentFilters: React.FC<AssessmentFiltersProps> = ({
  categories,
  selectedCategory,
  onCategoryChange,
  subjects = [],
  selectedSubjectId = null,
  onSubjectChange,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: spacing.xs }}>
        {categories.map((c) => {
          const active = c.id === selectedCategory;
          return (
            <Pressable
              key={c.id}
              onPress={() => onCategoryChange(c.id)}
              style={[
                styles.chip,
                {
                  backgroundColor: active ? `${colors.primary}18` : palette.surface,
                  borderColor: active ? colors.primary : palette.border,
                  borderRadius: radius.full,
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.xs,
                },
              ]}
            >
              <Text
                style={{
                  color: active ? colors.primary : palette.textSecondary,
                  fontSize: fontSizes.xs,
                  fontWeight: '700',
                }}
              >
                {c.label}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>

      {subjects.length > 1 && onSubjectChange ? (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          style={{ marginTop: spacing.xs }}
          contentContainerStyle={{ gap: spacing.xs }}
        >
          <Pressable
            onPress={() => onSubjectChange(null)}
            style={[
              styles.chip,
              {
                backgroundColor: selectedSubjectId == null ? `${colors.primary}12` : palette.surface,
                borderColor: palette.border,
                borderRadius: radius.full,
                paddingHorizontal: spacing.sm,
                paddingVertical: 4,
              },
            ]}
          >
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
              All subjects
            </Text>
          </Pressable>
          {subjects.map((s) => {
            const active = selectedSubjectId === s.id;
            return (
              <Pressable
                key={s.id}
                onPress={() => onSubjectChange(s.id)}
                style={[
                  styles.chip,
                  {
                    backgroundColor: active ? `${colors.primary}12` : palette.surface,
                    borderColor: active ? colors.primary : palette.border,
                    borderRadius: radius.full,
                    paddingHorizontal: spacing.sm,
                    paddingVertical: 4,
                  },
                ]}
              >
                <Text
                  style={{
                    color: active ? colors.primary : palette.textSecondary,
                    fontSize: fontSizes.xs,
                    fontWeight: '600',
                  }}
                >
                  {s.label}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  chip: { borderWidth: StyleSheet.hairlineWidth },
});
