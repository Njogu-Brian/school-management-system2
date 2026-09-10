import {
  buildPerformanceTrend,
  buildSubjectProgress,
  compareByAcademicOrder,
  computeTrendDelta,
  progressDirection,
  sittingGroupKey,
  sittingKind,
  sittingLabel,
  useStudentAssessmentHistory,
  type AssessmentHistoryItem,
} from '@erp/core';
import {
  FilterChip,
  FilterChipRow,
  ProgressTrendPanel,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useMemo, useRef, useState } from 'react';
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

type YearFilter = 'all' | string;
type TermFilter = 'all' | number;
type ExamFilter = 'all' | string;

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
  const [yearFilter, setYearFilter] = useState<YearFilter>('all');
  const [termFilter, setTermFilter] = useState<TermFilter>('all');
  const [examFilter, setExamFilter] = useState<ExamFilter>('all');
  const didInitYear = useRef(false);

  useEffect(() => {
    didInitYear.current = false;
    setYearFilter('all');
    setTermFilter('all');
    setExamFilter('all');
  }, [studentId]);

  useEffect(() => {
    if (historyQuery.hasNextPage && !historyQuery.isFetchingNextPage) {
      void historyQuery.fetchNextPage();
    }
  }, [historyQuery.hasNextPage, historyQuery.isFetchingNextPage, historyQuery.fetchNextPage]);

  const items = useMemo(
    () => historyQuery.data?.pages.flatMap((p) => p.rows) ?? [],
    [historyQuery.data],
  );

  const years = useMemo(() => uniqueYears(items), [items]);
  const terms = useMemo(() => uniqueTerms(items, yearFilter), [items, yearFilter]);
  const exams = useMemo(() => uniqueSittings(items, yearFilter, termFilter), [items, yearFilter, termFilter]);

  useEffect(() => {
    if (!didInitYear.current && years.length > 0) {
      didInitYear.current = true;
      setYearFilter(years[0].key);
    }
  }, [years]);

  useEffect(() => {
    if (yearFilter !== 'all' && !years.some((y) => y.key === yearFilter)) setYearFilter('all');
  }, [yearFilter, years]);
  useEffect(() => {
    if (termFilter !== 'all' && !terms.some((t) => t.id === termFilter)) setTermFilter('all');
  }, [termFilter, terms]);
  useEffect(() => {
    if (examFilter !== 'all' && !exams.some((e) => e.key === examFilter)) setExamFilter('all');
  }, [examFilter, exams]);

  const filtered = useMemo(
    () =>
      items.filter((row) => {
        if (yearFilter !== 'all' && yearKey(row) !== yearFilter) return false;
        if (termFilter !== 'all' && row.termId !== termFilter) return false;
        if (examFilter !== 'all' && sittingGroupKey(row) !== examFilter) return false;
        return true;
      }),
    [items, yearFilter, termFilter, examFilter],
  );

  const overallPoints = useMemo(() => buildPerformanceTrend(filtered), [filtered]);
  const overallDelta = useMemo(() => computeTrendDelta(overallPoints), [overallPoints]);
  const overallDirection = progressDirection(overallDelta);
  const subjectSeries = useMemo(() => buildSubjectProgress(filtered), [filtered]);

  const scopeHint =
    examFilter !== 'all'
      ? 'Selected exam sitting'
      : termFilter !== 'all'
        ? 'Selected term'
        : yearFilter !== 'all'
          ? 'Entire year'
          : 'All published sittings';

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

      {historyQuery.isLoading || (items.length === 0 && historyQuery.isFetchingNextPage) ? (
        <SkeletonListRows count={2} />
      ) : (
        <>
          {years.length > 0 ? (
            <FilterChipRow label="Year">
              <FilterChip
                label="All years"
                active={yearFilter === 'all'}
                onPress={() => setYearFilter('all')}
              />
              {years.map((y) => (
                <FilterChip
                  key={y.key}
                  label={y.label}
                  active={yearFilter === y.key}
                  onPress={() => setYearFilter(y.key)}
                />
              ))}
            </FilterChipRow>
          ) : null}
          {terms.length > 0 ? (
            <FilterChipRow label="Term">
              <FilterChip label="All terms" active={termFilter === 'all'} onPress={() => setTermFilter('all')} />
              {terms.map((t) => (
                <FilterChip
                  key={t.id}
                  label={t.label}
                  active={termFilter === t.id}
                  onPress={() => setTermFilter(t.id)}
                />
              ))}
            </FilterChipRow>
          ) : null}
          {exams.length > 0 ? (
            <FilterChipRow label="Exam">
              <FilterChip label="All exams" active={examFilter === 'all'} onPress={() => setExamFilter('all')} />
              {exams.map((e) => (
                <FilterChip
                  key={e.key}
                  label={e.label}
                  active={examFilter === e.key}
                  onPress={() => setExamFilter(e.key)}
                />
              ))}
            </FilterChipRow>
          ) : null}

          <ProgressTrendPanel
            title="Overall progress"
            subtitle={scopeHint}
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

function yearKey(row: AssessmentHistoryItem): string {
  if (row.academicYearLabel) return String(row.academicYearLabel);
  if (row.academicYearId != null) return `id:${row.academicYearId}`;
  return 'unknown';
}

function uniqueYears(items: AssessmentHistoryItem[]) {
  const map = new Map<string, string>();
  for (const row of items) {
    const key = yearKey(row);
    if (key === 'unknown') continue;
    map.set(key, row.academicYearLabel ? String(row.academicYearLabel) : `Year ${row.academicYearId}`);
  }
  return [...map.entries()]
    .map(([key, label]) => ({ key, label }))
    .sort((a, b) => b.label.localeCompare(a.label, undefined, { numeric: true }));
}

function uniqueTerms(items: AssessmentHistoryItem[], yearFilter: YearFilter) {
  const map = new Map<number, { id: number; label: string; sort: AssessmentHistoryItem }>();
  for (const row of items) {
    if (yearFilter !== 'all' && yearKey(row) !== yearFilter) continue;
    if (row.termId == null) continue;
    const existing = map.get(row.termId);
    if (!existing || compareByAcademicOrder(row, existing.sort) < 0) {
      map.set(row.termId, {
        id: row.termId,
        label: row.termName ?? `Term ${row.termId}`,
        sort: row,
      });
    }
  }
  return [...map.values()].sort((a, b) => compareByAcademicOrder(a.sort, b.sort));
}

function uniqueSittings(items: AssessmentHistoryItem[], yearFilter: YearFilter, termFilter: TermFilter) {
  const map = new Map<string, { key: string; label: string; sort: AssessmentHistoryItem }>();
  for (const row of items) {
    if (row.scorePercent == null) continue;
    if (sittingKind(row) === 'report' || sittingKind(row) === 'other') continue;
    if (yearFilter !== 'all' && yearKey(row) !== yearFilter) continue;
    if (termFilter !== 'all' && row.termId !== termFilter) continue;
    const key = sittingGroupKey(row);
    const existing = map.get(key);
    if (!existing || compareByAcademicOrder(row, existing.sort) < 0) {
      map.set(key, { key, label: sittingLabel(row), sort: row });
    }
  }
  return [...map.values()].sort((a, b) => compareByAcademicOrder(a.sort, b.sort));
}
