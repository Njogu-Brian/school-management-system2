/** `GET /students/{id}/assessment-history` row */
export interface AssessmentHistoryRecord {
  id: string;
  type: string;
  type_label: string;
  title: string;
  subject_id: number | null;
  subject_name: string | null;
  academic_year_id: number | null;
  term_id: number | null;
  assessed_on: string | null;
  score_raw: number | null;
  score_max: number | null;
  score_display: string | null;
  score_percent: number | null;
  grade_label: string | null;
  performance_level: PerformanceLevelRecord | null;
  status: string;
  remark: string | null;
  legacy_source: { table: string; id: number };
}

export interface PerformanceLevelRecord {
  id: number;
  code: string;
  name: string;
}

/** `GET /students/{id}/academic-summary` */
export interface AcademicSummaryRecord {
  student_id: number;
  academic_year_id: number | null;
  term_id: number | null;
  current_term_id: number | null;
  exam_average: number | null;
  latest_overall_percentage: number | null;
  latest_overall_grade: string | null;
  latest_performance_level: PerformanceLevelRecord | null;
  report_cards_count: number;
  published_report_cards_count: number;
  marks_recorded_count: number;
  portfolio_count: number;
  weekly_assessment_count: number;
  latest_report_card_id: number | null;
  assessment_counts_by_type: Record<string, number>;
}

export interface AssessmentHistoryMeta {
  student_id: number;
  current_term_id: number | null;
}

/** `GET /report-cards` list row */
export interface ReportCardListRecord {
  id: number;
  student_id: number;
  student_name?: string;
  class_id: number;
  class_name?: string;
  term_id: number;
  academic_year_id: number;
  overall_marks: number;
  overall_percentage: number;
  overall_grade?: string | null;
  status: 'draft' | 'published';
  generated_at?: string;
  created_at: string;
  updated_at: string;
  subjects: unknown[];
}

export interface ReportCardSubjectRecord {
  subject_id: number;
  subject_name: string;
  marks: number;
  total_marks: number;
  percentage: number;
  grade: string;
  remarks?: string | null;
  position?: number | null;
}

export interface ReportCardSkillRecord {
  skill_name: string;
  rating: 'excellent' | 'good' | 'average' | 'needs_improvement';
  comment?: string | null;
}

/** `GET /report-cards/{id}` */
export interface ReportCardDetailRecord {
  id: number;
  student_id: number;
  student_name?: string;
  class_id: number;
  class_name?: string;
  term_id: number;
  academic_year_id: number;
  overall_marks: number;
  overall_percentage: number;
  overall_grade?: string | null;
  overall_position?: number | null;
  class_position?: number | null;
  stream_position?: number | null;
  subjects: ReportCardSubjectRecord[];
  skills?: ReportCardSkillRecord[];
  teacher_comment?: string | null;
  principal_comment?: string | null;
  status: 'draft' | 'published';
  generated_at?: string;
  created_at: string;
  updated_at: string;
}

export interface AssessmentHistoryFilters {
  page?: number;
  per_page?: number;
  academic_year_id?: number;
  term_id?: number;
  subject_id?: number;
  /** Comma-separated canonical API types */
  type?: string;
}

/** Client-side filter chips */
export type AssessmentDisplayCategory =
  | 'all'
  | 'cat'
  | 'quiz'
  | 'assignment'
  | 'exam'
  | 'portfolio'
  | 'report_card';

export interface AssessmentHistoryItem {
  id: string;
  type: string;
  typeLabel: string;
  displayCategory: AssessmentDisplayCategory;
  title: string;
  subjectId: number | null;
  subjectName: string | null;
  academicYearId: number | null;
  termId: number | null;
  assessedOn: string | null;
  scoreDisplay: string | null;
  scorePercent: number | null;
  gradeLabel: string | null;
  performanceLevel: PerformanceLevelRecord | null;
  status: string;
  remark: string | null;
  legacySource: { table: string; id: number };
}

export interface AcademicSummary {
  studentId: number;
  currentTermId: number | null;
  examAverage: number | null;
  latestOverallPercentage: number | null;
  latestOverallGrade: string | null;
  latestPerformanceLevel: PerformanceLevelRecord | null;
  reportCardsCount: number;
  publishedReportCardsCount: number;
  marksRecordedCount: number;
  portfolioCount: number;
  weeklyAssessmentCount: number;
  latestReportCardId: number | null;
  assessmentCountsByType: Record<string, number>;
  totalAssessmentCount: number;
}

export interface PerformanceTrendPoint {
  label: string;
  percentage: number;
  assessedOn: string | null;
  kind: 'report_card' | 'assessment';
}
