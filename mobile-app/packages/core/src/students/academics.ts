import type {
  AcademicSummary,
  AcademicSummaryRecord,
  AssessmentDisplayCategory,
  AssessmentHistoryItem,
  AssessmentHistoryRecord,
  PerformanceTrendPoint,
} from '../types/studentAcademics';

/** Map API canonical type → UI timeline bucket */
export function mapToDisplayCategory(apiType: string): AssessmentDisplayCategory {
  switch (apiType) {
    case 'cat':
      return 'cat';
    case 'speed_test':
      return 'quiz';
    case 'assignment':
    case 'weekly_assessment':
      return 'assignment';
    case 'portfolio':
    case 'project':
      return 'portfolio';
    case 'report_card_term':
      return 'report_card';
    case 'traditional_exam':
    case 'cbc_summative':
    case 'cbc_formative':
    case 'oral':
    case 'practical':
    default:
      return 'exam';
  }
}

/** Map UI filter chip → API `type` query (comma-separated) */
export function displayCategoryToApiTypes(category: AssessmentDisplayCategory): string | undefined {
  switch (category) {
    case 'all':
      return undefined;
    case 'cat':
      return 'cat';
    case 'quiz':
      return 'speed_test';
    case 'assignment':
      return 'assignment,weekly_assessment';
    case 'exam':
      return 'traditional_exam,cbc_summative,cbc_formative,oral,practical';
    case 'portfolio':
      return 'portfolio,project';
    case 'report_card':
      return 'report_card_term';
    default:
      return undefined;
  }
}

export function normalizeAssessmentHistoryRow(row: AssessmentHistoryRecord): AssessmentHistoryItem {
  return {
    id: row.id,
    type: row.type,
    typeLabel: row.type_label,
    displayCategory: mapToDisplayCategory(row.type),
    title: row.title,
    subjectId: row.subject_id,
    subjectName: row.subject_name,
    academicYearId: row.academic_year_id,
    termId: row.term_id,
    assessedOn: row.assessed_on,
    scoreDisplay: row.score_display,
    scorePercent: row.score_percent,
    gradeLabel: row.grade_label,
    performanceLevel: row.performance_level,
    status: row.status,
    remark: row.remark,
    legacySource: row.legacy_source,
  };
}

export function normalizeAcademicSummary(row: AcademicSummaryRecord): AcademicSummary {
  const counts = row.assessment_counts_by_type ?? {};
  const totalAssessmentCount = Object.values(counts).reduce((sum, n) => sum + (n ?? 0), 0);

  return {
    studentId: row.student_id,
    currentTermId: row.current_term_id,
    examAverage: row.exam_average,
    latestOverallPercentage: row.latest_overall_percentage,
    latestOverallGrade: row.latest_overall_grade,
    latestPerformanceLevel: row.latest_performance_level,
    reportCardsCount: row.report_cards_count,
    publishedReportCardsCount: row.published_report_cards_count,
    marksRecordedCount: row.marks_recorded_count,
    portfolioCount: row.portfolio_count,
    weeklyAssessmentCount: row.weekly_assessment_count,
    latestReportCardId: row.latest_report_card_id,
    assessmentCountsByType: counts,
    totalAssessmentCount,
  };
}

/** Term-over-term and scored events for sparkline / list trend */
export function buildPerformanceTrend(items: AssessmentHistoryItem[]): PerformanceTrendPoint[] {
  const reportCards = items
    .filter((i) => i.displayCategory === 'report_card' && i.scorePercent != null)
    .sort((a, b) => (a.assessedOn ?? '').localeCompare(b.assessedOn ?? ''));

  if (reportCards.length >= 2) {
    return reportCards.map((rc) => ({
      label: rc.title.replace(/^Term Report —\s*/i, '').trim() || 'Term',
      percentage: rc.scorePercent as number,
      assessedOn: rc.assessedOn,
      kind: 'report_card' as const,
    }));
  }

  const scored = items
    .filter((i) => i.displayCategory !== 'report_card' && i.scorePercent != null)
    .sort((a, b) => (a.assessedOn ?? '').localeCompare(b.assessedOn ?? ''))
    .slice(-8);

  return scored.map((row) => ({
    label: row.subjectName ? `${row.subjectName}` : row.typeLabel,
    percentage: row.scorePercent as number,
    assessedOn: row.assessedOn,
    kind: 'assessment' as const,
  }));
}

export function computeTrendDelta(points: PerformanceTrendPoint[]): number | null {
  if (points.length < 2) return null;
  const last = points[points.length - 1].percentage;
  const prev = points[points.length - 2].percentage;
  return Math.round((last - prev) * 10) / 10;
}

export function displayCategoryLabel(category: AssessmentDisplayCategory): string {
  const labels: Record<AssessmentDisplayCategory, string> = {
    all: 'All',
    cat: 'CAT',
    quiz: 'Quiz',
    assignment: 'Assignment',
    exam: 'Exam',
    portfolio: 'Portfolio',
    report_card: 'Report Card',
  };
  return labels[category];
}

export const ASSESSMENT_FILTER_CATEGORIES: AssessmentDisplayCategory[] = [
  'all',
  'cat',
  'quiz',
  'assignment',
  'exam',
  'portfolio',
  'report_card',
];
