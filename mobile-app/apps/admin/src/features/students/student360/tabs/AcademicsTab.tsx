import {
  ASSESSMENT_FILTER_CATEGORIES,
  buildPerformanceTrend,
  computeTrendDelta,
  displayCategoryLabel,
  type AssessmentDisplayCategory,
  type AssessmentHistoryItem,
  useStudentAcademicSummary,
  useStudentAssessmentHistory,
  useStudentReportCardDetail,
  useStudentReportCards,
} from '@erp/core';
import {
  AcademicOverviewCard,
  AssessmentFilters,
  AssessmentTimeline,
  EmptyState,
  PerformanceTrend,
  ReportCardHistoryList,
  type AssessmentFilterOption,
  type AssessmentTimelineItemData,
  type ReportCardHistoryItemData,
  useTheme,
} from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { formatDateLabel, formatPercent } from '../utils/formatters';

export interface AcademicsTabProps {
  studentId: number;
  onOpenReportCard?: (reportCardId: number) => void;
}

export const AcademicsTab: React.FC<AcademicsTabProps> = ({ studentId, onOpenReportCard }) => {
  const { colors, spacing, typography } = useTheme();
  const [category, setCategory] = useState<AssessmentDisplayCategory>('all');
  const [subjectId, setSubjectId] = useState<number | null>(null);

  const summaryQuery = useStudentAcademicSummary(studentId);
  const historyQuery = useStudentAssessmentHistory(studentId, {
    category,
    subjectId,
  });
  const reportCardsQuery = useStudentReportCards(studentId);

  const latestRcId = summaryQuery.data?.latestReportCardId;
  const latestRcQuery = useStudentReportCardDetail(latestRcId, {
    enabled: (latestRcId ?? 0) > 0,
  });

  const historyItems = useMemo(
    () => historyQuery.data?.pages.flatMap((p) => p.rows) ?? [],
    [historyQuery.data],
  );

  const subjectOptions = useMemo(() => {
    const map = new Map<number, string>();
    for (const row of historyItems) {
      if (row.subjectId != null && row.subjectName) {
        map.set(row.subjectId, row.subjectName);
      }
    }
    return [...map.entries()].map(([id, label]) => ({ id, label }));
  }, [historyItems]);

  const filterOptions: AssessmentFilterOption[] = useMemo(
    () =>
      ASSESSMENT_FILTER_CATEGORIES.map((id) => ({
        id,
        label: displayCategoryLabel(id),
      })),
    [],
  );

  const trendPoints = useMemo(() => buildPerformanceTrend(historyItems), [historyItems]);
  const trendDelta = useMemo(() => computeTrendDelta(trendPoints), [trendPoints]);

  const reportCardPercentByTerm = useMemo(() => {
    const m = new Map<number, number>();
    for (const row of historyItems) {
      if (
        row.displayCategory === 'report_card' &&
        row.legacySource.table === 'report_cards' &&
        row.scorePercent != null
      ) {
        m.set(row.legacySource.id, row.scorePercent);
      }
    }
    return m;
  }, [historyItems]);

  const reportCardListItems = useMemo((): ReportCardHistoryItemData[] => {
    const cards = reportCardsQuery.data ?? [];
    return cards.map((rc) => {
      const pct = reportCardPercentByTerm.get(rc.id);
      const pctLabel =
        pct != null
          ? formatPercent(pct)
          : rc.overall_percentage > 0
            ? formatPercent(rc.overall_percentage)
            : 'View detail';
      return {
        id: rc.id,
        title: rc.class_name ? `Report · ${rc.class_name}` : `Report card #${rc.id}`,
        subtitle: `Term ${rc.term_id} · Year ${rc.academic_year_id}`,
        status: rc.status,
        percentageLabel: pctLabel,
        generatedAtLabel: rc.generated_at ? formatDateLabel(rc.generated_at) : undefined,
      };
    });
  }, [reportCardsQuery.data, reportCardPercentByTerm]);

  const timelineItems = useMemo((): AssessmentTimelineItemData[] => {
    return historyItems.map((row: AssessmentHistoryItem) => ({
      id: row.id,
      title: row.title,
      subtitle: [row.typeLabel, row.subjectName].filter(Boolean).join(' · '),
      occurredAtLabel: formatDateLabel(row.assessedOn),
      scoreDisplay: row.scoreDisplay,
      gradeLabel: row.gradeLabel,
      displayCategory: row.displayCategory,
      status: row.status,
    }));
  }, [historyItems]);

  const summary = summaryQuery.data;
  const positionLabel = useMemo(() => {
    const rc = latestRcQuery.data;
    if (rc?.class_position != null) return `#${rc.class_position}`;
    if (rc?.overall_position != null) return `#${rc.overall_position}`;
    if (rc?.stream_position != null) return `#${rc.stream_position}`;
    return '—';
  }, [latestRcQuery.data]);

  const overview = useMemo(
    () => ({
      average: formatPercent(summary?.examAverage ?? summary?.latestOverallPercentage ?? null),
      grade: summary?.latestOverallGrade ?? summary?.latestPerformanceLevel?.code ?? '—',
      position: positionLabel,
      assessmentCount: String(
        summary?.totalAssessmentCount ?? summary?.marksRecordedCount ?? historyItems.length,
      ),
      trendLabel:
        trendPoints.length >= 2
          ? 'Term / assessment progression'
          : 'Add more terms to see trend',
      trendDelta,
    }),
    [summary, positionLabel, historyItems.length, trendPoints.length, trendDelta],
  );

  const isLoading =
    summaryQuery.isLoading || (historyQuery.isLoading && !historyQuery.data);

  if (isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (summaryQuery.isError || historyQuery.isError) {
    return (
      <EmptyState
        title="Could not load academics"
        message="Unable to load academic summary or assessment history."
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => {
          void summaryQuery.refetch();
          void historyQuery.refetch();
          void reportCardsQuery.refetch();
        }}
      />
    );
  }

  return (
    <View>
      <AcademicOverviewCard
        average={overview.average}
        grade={overview.grade}
        position={overview.position}
        assessmentCount={overview.assessmentCount}
        trendLabel={overview.trendLabel}
        trendDelta={overview.trendDelta}
      />

      <PerformanceTrend
        points={trendPoints.map((p) => ({ label: p.label, percentage: p.percentage }))}
      />

      <AssessmentFilters
        categories={filterOptions}
        selectedCategory={category}
        onCategoryChange={(id) => setCategory(id as AssessmentDisplayCategory)}
        subjects={subjectOptions}
        selectedSubjectId={subjectId}
        onSubjectChange={setSubjectId}
      />

      <AssessmentTimeline items={timelineItems} />

      {historyQuery.hasNextPage ? (
        <Pressable
          onPress={() => void historyQuery.fetchNextPage()}
          disabled={historyQuery.isFetchingNextPage}
          style={{
            alignSelf: 'center',
            marginTop: spacing.sm,
            paddingVertical: spacing.xs,
            paddingHorizontal: spacing.md,
          }}
        >
          <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.body.fontSize }}>
            {historyQuery.isFetchingNextPage ? 'Loading…' : 'Load more'}
          </Text>
        </Pressable>
      ) : null}

      <ReportCardHistoryList
        items={reportCardListItems}
        onPressItem={onOpenReportCard}
      />

      {reportCardsQuery.isLoading ? (
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.sm }} />
      ) : null}
    </View>
  );
};
