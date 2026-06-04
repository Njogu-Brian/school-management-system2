import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { StudentTimelineEventData } from './types';

const ICON: Record<StudentTimelineEventData['kind'], keyof typeof Ionicons.glyphMap> = {
  payment: 'cash-outline',
  invoice: 'receipt-outline',
  enrollment: 'school-outline',
  update: 'create-outline',
  other: 'ellipse-outline',
};

export interface StudentTimelineProps {
  title?: string;
  events: StudentTimelineEventData[];
  emptyMessage?: string;
}

export const StudentTimeline: React.FC<StudentTimelineProps> = ({
  title = 'Recent activity',
  events,
  emptyMessage = 'No recent activity.',
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={{ marginTop: spacing.md }}>
      <Text
        style={[
          styles.section,
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm },
        ]}
      >
        {title}
      </Text>
      {events.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>{emptyMessage}</Text>
      ) : (
        events.map((ev, index) => (
          <View key={ev.id} style={styles.row}>
            <View style={styles.lineCol}>
              <View
                style={[
                  styles.dot,
                  { backgroundColor: `${colors.primary}22`, borderRadius: radius.full },
                ]}
              >
                <Ionicons name={ICON[ev.kind]} size={14} color={colors.primary} />
              </View>
              {index < events.length - 1 ? (
                <View style={[styles.line, { backgroundColor: palette.border }]} />
              ) : null}
            </View>
            <View style={[styles.content, { paddingBottom: spacing.md }]}>
              <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '600' }}>
                {ev.title}
              </Text>
              {ev.subtitle ? (
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                  {ev.subtitle}
                </Text>
              ) : null}
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                {ev.occurredAtLabel}
              </Text>
            </View>
          </View>
        ))
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  section: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase' },
  row: { flexDirection: 'row' },
  lineCol: { width: 32, alignItems: 'center' },
  dot: { width: 28, height: 28, alignItems: 'center', justifyContent: 'center' },
  line: { width: 2, flex: 1, minHeight: 16 },
  content: { flex: 1 },
});
