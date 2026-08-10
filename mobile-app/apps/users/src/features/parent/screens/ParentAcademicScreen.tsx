import {
  buildPerformanceTrend,
  buildSubjectProgress,
  computeTrendDelta,
  progressDirection,
  useInfiniteStudentList,
  useStudentAssessmentHistory,
} from '@erp/core';
import {
  EmptyState,
  ProgressTrendPanel,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';
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

function ChildProgressBlock({
  studentId,
  name,
  meta,
}: {
  studentId: number;
  name: string;
  meta: string;
}) {
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
        backgroundColor: palette.surface,
        borderColor: palette.border,
        borderWidth: 1,
        borderRadius: radius.lg,
        padding: spacing.md,
        marginBottom: spacing.md,
      }}
    >
      <Text style={{ color: palette.textPrimary, fontWeight: '800', fontSize: typography.bodyLarge?.fontSize ?? 17 }}>
        {name}
      </Text>
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2, marginBottom: spacing.sm }}>
        {meta}
      </Text>

      {historyQuery.isLoading ? (
        <SkeletonListRows count={2} />
      ) : (
        <>
          <ProgressTrendPanel
            title="Overall progress"
            subtitle="Across recent assessments / report cards"
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
    </View>
  );
}

export const ParentAcademicScreen: React.FC = () => {
  const { spacing } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
  const listQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 40,
  });
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      {listQuery.isLoading ? (
        <SkeletonListRows count={3} />
      ) : students.length === 0 ? (
        <EmptyState
          title="No children linked"
          message="Link children to view academic progress graphs."
          icon="school-outline"
        />
      ) : (
        <FlatList
          data={students}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}
          ListHeaderComponent={
            <Text
              style={{
                marginBottom: spacing.sm,
                fontWeight: '600',
                color: '#64748B',
              }}
            >
              Progress trends · overall and by subject
            </Text>
          }
          renderItem={({ item }) => (
            <ChildProgressBlock
              studentId={item.id}
              name={item.fullName}
              meta={[item.admissionNumber, item.className].filter(Boolean).join(' · ')}
            />
          )}
        />
      )}
    </ScreenContainer>
  );
};
