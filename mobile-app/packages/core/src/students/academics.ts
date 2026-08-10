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

/**
 * Compact exam / term label for progress charts.
 * Examples: "Mid Term 1", "End Term 2", "Opener Term 3".
 */
export function assessmentChartLabel(row: AssessmentHistoryItem): string {
  const raw = ((row.title ?? '').trim() || (row.typeLabel ?? '').trim() || 'Score')
    .replace(/^Term Report\s*[—\-–]\s*/i, '')
    .replace(/\s+/g, ' ')
    .trim();

  return formatExamProgressLabel(raw);
}

/** Normalize free-form exam titles into short chart markers. */
export function formatExamProgressLabel(raw: string): string {
  const cleaned = raw.replace(/\s+/g, ' ').trim();
  if (!cleaned) return 'Score';

  const lower = cleaned.toLowerCase();

  // Extract term number from common patterns (Term 2, T2, Term2, …).
  const termMatch =
    lower.match(/\bterm\s*[-_]?\s*([1-3])\b/) ||
    lower.match(/\bt\s*([1-3])\b/) ||
    lower.match(/\b([1-3])\s*(?:st|nd|rd|th)?\s*term\b/);
  const termNum = termMatch?.[1] ?? null;

  if (/\bopener\b/.test(lower) || /\bopening\b/.test(lower)) {
    return termNum ? `Opener Term ${termNum}` : 'Opener';
  }
  if (/\bmid[\s_-]*term\b/.test(lower) || /\bmidterm\b/.test(lower) || /\bmid\b/.test(lower)) {
    return termNum ? `Mid Term ${termNum}` : 'Mid Term';
  }
  if (
    /\bend[\s_-]*of[\s_-]*term\b/.test(lower) ||
    /\bend[\s_-]*term\b/.test(lower) ||
    /\bendterm\b/.test(lower) ||
    /\bfinal\b/.test(lower)
  ) {
    return termNum ? `End Term ${termNum}` : 'End Term';
  }
  if (/^term\s*[1-3]$/i.test(cleaned) || /^t\s*[1-3]$/i.test(cleaned)) {
    return termNum ? `Term ${termNum}` : titleCaseWords(cleaned);
  }

  // Fallback: title-case, keep short.
  const titled = titleCaseWords(cleaned);
  return titled.length > 22 ? `${titled.slice(0, 20)}…` : titled;
}

function titleCaseWords(value: string): string {
  return value
    .toLowerCase()
    .split(/[\s_]+/)
    .filter(Boolean)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

/** Term-over-term and scored events for sparkline / list trend */
export function buildPerformanceTrend(items: AssessmentHistoryItem[]): PerformanceTrendPoint[] {
  const reportCards = items
    .filter((i) => i.displayCategory === 'report_card' && i.scorePercent != null)
    .sort((a, b) => (a.assessedOn ?? '').localeCompare(b.assessedOn ?? ''));

  if (reportCards.length >= 2) {
    return reportCards.map((rc) => ({
      label: assessmentChartLabel(rc) || 'Term',
      percentage: rc.scorePercent as number,
      assessedOn: rc.assessedOn,
      kind: 'report_card' as const,
    }));
  }

  const scored = items
    .filter((i) => i.displayCategory !== 'report_card' && i.scorePercent != null)
    .sort((a, b) => (a.assessedOn ?? '').localeCompare(b.assessedOn ?? ''))
    .slice(-12);

  return scored.map((row) => ({
    label: assessmentChartLabel(row),
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

export type ProgressDirection = 'up' | 'down' | 'stale';

export function progressDirection(delta: number | null, staleThreshold = 0.5): ProgressDirection {
  if (delta == null || Math.abs(delta) < staleThreshold) return 'stale';
  return delta > 0 ? 'up' : 'down';
}

export interface SubjectProgressSeries {
  subjectId: number;
  subjectName: string;
  points: PerformanceTrendPoint[];
  delta: number | null;
  direction: ProgressDirection;
  latestPercent: number | null;
}

/** Build per-subject scored trends (oldest → newest) from assessment history. */
export function buildSubjectProgress(items: AssessmentHistoryItem[]): SubjectProgressSeries[] {
  const bySubject = new Map<number, AssessmentHistoryItem[]>();
  for (const row of items) {
    if (row.subjectId == null || row.scorePercent == null) continue;
    if (row.displayCategory === 'report_card') continue;
    const list = bySubject.get(row.subjectId) ?? [];
    list.push(row);
    bySubject.set(row.subjectId, list);
  }

  const series: SubjectProgressSeries[] = [];
  for (const [subjectId, rows] of bySubject) {
    const sorted = [...rows].sort((a, b) => (a.assessedOn ?? '').localeCompare(b.assessedOn ?? ''));
    // Prefer formal exams (mid/end/opener) when available so markers stay readable.
    const examRows = sorted.filter((r) => r.displayCategory === 'exam');
    const source = examRows.length > 0 ? examRows : sorted;
    const points: PerformanceTrendPoint[] = source.slice(-12).map((row) => ({
      label: assessmentChartLabel(row),
      percentage: row.scorePercent as number,
      assessedOn: row.assessedOn,
      kind: 'assessment' as const,
    }));
    if (points.length === 0) continue;
    const delta = computeTrendDelta(points);
    series.push({
      subjectId,
      subjectName: sorted[sorted.length - 1]?.subjectName ?? `Subject ${subjectId}`,
      points,
      delta,
      direction: progressDirection(delta),
      latestPercent: points[points.length - 1]?.percentage ?? null,
    });
  }

  return series.sort((a, b) => a.subjectName.localeCompare(b.subjectName));
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
