import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import type { AssessmentDisplayCategoryUi } from './types';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import type { AssessmentTimelineItemData } from './types';

const ICON: Record<AssessmentDisplayCategoryUi, keyof typeof Ionicons.glyphMap> = {
  all: 'list-outline',
  cat: 'clipboard-outline',
  quiz: 'flash-outline',
  assignment: 'document-text-outline',
  exam: 'school-outline',
  portfolio: 'folder-outline',
  report_card: 'ribbon-outline',
};

const COLOR: Record<AssessmentDisplayCategoryUi, string> = {
  all: '#64748b',
  cat: '#2563eb',
  quiz: '#7c3aed',
  assignment: '#0891b2',
  exam: '#0f766e',
  portfolio: '#ca8a04',
  report_card: '#be185d',
};

export interface AssessmentTimelineProps {
  title?: string;
  items: AssessmentTimelineItemData[];
  emptyMessage?: string;
}

export const AssessmentTimeline: React.FC<AssessmentTimelineProps> = ({
  title = 'Assessment timeline',
  items,
  emptyMessage = 'No assessments match these filters.',
}) => {
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <View style={{ marginTop: spacing.md }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: 0.4,
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      {items.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>{emptyMessage}</Text>
      ) : (
        items.map((item, index) => {
          const tint = COLOR[item.displayCategory] ?? COLOR.exam;
          return (
            <View key={item.id} style={styles.row}>
              <View style={styles.lineCol}>
                <View
                  style={[
                    styles.dot,
                    { backgroundColor: `${tint}22`, borderRadius: radius.full },
                  ]}
                >
                  <Ionicons name={ICON[item.displayCategory] ?? ICON.exam} size={14} color={tint} />
                </View>
                {index < items.length - 1 ? (
                  <View style={[styles.line, { backgroundColor: palette.border }]} />
                ) : null}
              </View>
              <View style={[styles.content, { paddingBottom: spacing.md }]}>
                <Text style={{ color: palette.textPrimary, fontSize: typography.body.fontSize, fontWeight: '600' }}>
                  {item.title}
                </Text>
                {item.subtitle ? (
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                    {item.subtitle}
                  </Text>
                ) : null}
                <View style={styles.metaRow}>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                    {item.occurredAtLabel}
                  </Text>
                  {item.scoreDisplay ? (
                    <Text style={{ color: tint, fontSize: typography.caption.fontSize, fontWeight: '700' }}>
                      {item.scoreDisplay}
                      {item.gradeLabel ? ` · ${item.gradeLabel}` : ''}
                    </Text>
                  ) : null}
                </View>
              </View>
            </View>
          );
        })
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row' },
  lineCol: { width: 32, alignItems: 'center' },
  dot: { width: 28, height: 28, alignItems: 'center', justifyContent: 'center' },
  line: { width: 2, flex: 1, minHeight: 16 },
  content: { flex: 1 },
  metaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
    gap: 8,
  },
});
