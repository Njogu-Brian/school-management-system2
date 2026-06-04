export type AssessmentDisplayCategoryUi =
  | 'all'
  | 'cat'
  | 'quiz'
  | 'assignment'
  | 'exam'
  | 'portfolio'
  | 'report_card';

export interface AcademicOverviewCardProps {
  average: string;
  grade: string;
  position: string;
  assessmentCount: string;
  trendLabel: string;
  trendDelta: number | null;
  isLoading?: boolean;
}

export interface AssessmentTimelineItemData {
  id: string;
  title: string;
  subtitle?: string;
  occurredAtLabel: string;
  scoreDisplay?: string | null;
  gradeLabel?: string | null;
  displayCategory: AssessmentDisplayCategoryUi;
  status?: string;
}

export interface ReportCardHistoryItemData {
  id: number;
  title: string;
  subtitle: string;
  status: 'draft' | 'published';
  percentageLabel: string;
  generatedAtLabel?: string;
}

export interface PerformanceTrendPointData {
  label: string;
  percentage: number;
}

export interface AssessmentFilterOption {
  id: AssessmentDisplayCategoryUi;
  label: string;
}

export interface SubjectFilterOption {
  id: number;
  label: string;
}
