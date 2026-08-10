import {
  buildPerformanceTrend,
  buildSubjectProgress,
  computeTrendDelta,
  progressDirection,
  useStudentAssessmentHistory,
} from '@erp/core';
import { ProgressTrendPanel, SkeletonListRows, Soft3DIcon, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;

const ACTIONS: Array<{
  label: string;
  subtitle: string;
  icon: 'school-outline' | 'calendar-outline' | 'book-outline';
  tone: 'indigo' | 'emerald' | 'amber';
  route: 'ChildResults' | 'ChildAttendance' | 'ChildHomework';
}> = [
  { label: 'Results', subtitle: 'Report forms & grades', icon: 'school-outline', tone: 'indigo', route: 'ChildResults' },
  { label: 'Attendance', subtitle: 'Present / absent days', icon: 'calendar-outline', tone: 'emerald', route: 'ChildAttendance' },
  { label: 'Homework', subtitle: 'Assignments & tasks', icon: 'book-outline', tone: 'amber', route: 'ChildHomework' },
];

export interface ChildAcademicProgressSectionProps {
  studentId: number;
  name?: string;
  meta?: string;
  /** When true, show Results / Attendance / Homework shortcuts under the charts. */
  showQuickLinks?: boolean;
  /** Hide the outer name/meta header (e.g. Results screen already has a title). */
  hideIdentity?: boolean;
}

/** Overall + per-subject exam progress bars for one child. */
export const ChildAcademicProgressSection: React.FC<ChildAcademicProgressSectionProps> = ({
  studentId,
  name,
  meta,
  showQuickLinks = false,
  hideIdentity = false,
}) => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();
  const historyQuery = useStudentAssessmentHistory(studentId, { category: 'all' });
  const items = useMemo(
    () => historyQuery.data?.pages.flatMap((p) => p.rows) ?? [],
    [historyQuery.data],
  );
  const overallPoints = useMemo(() => buildPerformanceTrend(items), [items]);
  const overallDelta = useMemo(() => computeTrendDelta(overallPoints), [overallPoints]);
  const overallDirection = progressDirection(overallDelta);
  const subjectSeries = useMemo(() => buildSubjectProgress(items), [items]);

  return (
    <View
      style={{
        backgroundColor: hideIdentity ? 'transparent' : palette.surface,
        borderColor: hideIdentity ? 'transparent' : palette.border,
        borderWidth: hideIdentity ? 0 : 1,
        borderRadius: radius.lg,
        padding: hideIdentity ? 0 : spacing.md,
        marginBottom: spacing.md,
      }}
    >
      {!hideIdentity && name ? (
        <>
          <Text style={{ color: palette.textPrimary, fontWeight: '800', fontSize: typography.bodyLarge?.fontSize ?? 17 }}>
            {name}
          </Text>
          {meta ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: 2,
                marginBottom: spacing.sm,
              }}
            >
              {meta}
            </Text>
          ) : (
            <View style={{ marginBottom: spacing.sm }} />
          )}
        </>
      ) : null}

      {historyQuery.isLoading ? (
        <SkeletonListRows count={2} />
      ) : (
        <>
          <ProgressTrendPanel
            title="Overall progress"
            subtitle="Across recent exams / report cards"
            points={overallPoints.map((p) => ({ label: p.label, percentage: p.percentage }))}
            direction={overallDirection}
            delta={overallDelta}
          />
          {subjectSeries.length > 0 ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontWeight: '700',
                fontSize: typography.caption.fontSize,
                marginBottom: spacing.xs,
                marginTop: spacing.xs,
                textTransform: 'uppercase',
                letterSpacing: 0.4,
              }}
            >
              Per subject
            </Text>
          ) : null}
          {subjectSeries.map((s) => (
            <ProgressTrendPanel
              key={s.subjectId}
              title={s.subjectName}
              subtitle={s.latestPercent != null ? `Latest ${s.latestPercent.toFixed(0)}%` : undefined}
              points={s.points.map((p) => ({ label: p.label, percentage: p.percentage }))}
              direction={s.direction}
              delta={s.delta}
            />
          ))}
        </>
      )}

      {showQuickLinks ? (
        <View style={{ gap: spacing.sm, marginTop: spacing.sm }}>
          {ACTIONS.map((action) => (
            <Pressable
              key={action.route}
              onPress={() => navigation.navigate(action.route, { studentId })}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: spacing.md,
                paddingVertical: spacing.xs,
              }}
            >
              <Soft3DIcon name={action.icon} tone={action.tone} size={40} />
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{action.label}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {action.subtitle}
                </Text>
              </View>
            </Pressable>
          ))}
        </View>
      ) : null}
    </View>
  );
};
