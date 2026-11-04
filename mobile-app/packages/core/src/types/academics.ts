/** Workspace academics types (Sprint 7) — mirrors Sanctum API payloads. */

export type ExamStatus =
  | 'draft'
  | 'open'
  | 'marking'
  | 'moderation'
  | 'approved'
  | 'published'
  | 'locked'
  | string;

export interface ExamListRecord {
  id: number;
  name: string;
  exam_type_id: number | null;
  exam_type_name: string | null;
  academic_year_id: number | null;
  term_id: number | null;
  classroom_id: number | null;
  stream_id: number | null;
  subject_id: number | null;
  start_date: string | null;
  end_date: string | null;
  status: ExamStatus;
  total_marks: number;
  created_at: string;
  updated_at: string;
  classroom_name?: string | null;
  subject_name?: string | null;
}

export interface ExamMarkingOption {
  classroom_id: number;
  classroom_name: string;
  subject_id: number;
  subject_name: string;
}

export interface ExamListFilters {
  status?: string;
  /** When true, server returns exams in statuses the user may enter marks for. */
  for_mark_entry?: boolean;
  search?: string;
  academic_year_id?: number;
  term_id?: number;
  classroom_id?: number;
  page?: number;
  per_page?: number;
}

export interface ExamSessionRecord {
  id: number;
  name: string;
  exam_type_id: number | null;
  exam_type_name: string | null;
  academic_year_id: number | null;
  term_id: number | null;
  classroom_id: number | null;
  stream_id: number | null;
  classroom_name: string | null;
  term_name: string | null;
  status: string | null;
}

export interface MarkListRecord {
  id: number;
  exam_id: number;
  student_id: number;
  student_name: string;
  subject_id: number;
  marks: number;
  total_marks: number;
  remarks: string | null;
  percentage: number;
  created_at: string;
  updated_at: string;
}

export interface MarksListFilters {
  exam_id: number;
  subject_id: number;
  classroom_id: number;
}

export interface MarksMatrixStudent {
  id: number;
  full_name: string;
  admission_number: string;
  classroom_id: number;
  stream_id: number | null;
}

export interface MarksMatrixExam {
  id: number;
  name: string;
  subject_id: number;
  subject_name: string | null;
  max_marks: number;
  min_marks: number;
}

export interface MarksMatrixExistingMark {
  student_id: number;
  exam_id: number;
  marks: number | null;
  remarks: string | null;
}

export interface MarksMatrixContext {
  exam_types: { id: number; name: string; code?: string }[];
  classrooms: { id: number; name: string }[];
  streams: { id: number; name: string; classroom_id: number }[];
  academic_years?: { id: number; year?: string | number; label?: string; is_active?: boolean }[];
  active_academic_year_id?: number | null;
  terms?: { id: number; name: string; academic_year_id: number; is_current?: boolean }[];
  current_term_id?: number | null;
}

export interface MarksMatrixFilters {
  exam_type_id: number;
  classroom_id: number;
  stream_id?: number;
  academic_year_id?: number;
  term_id?: number;
}

export interface AcademicContext {
  academic_years: { id: number; year?: string | number; label?: string; is_active?: boolean }[];
  active_academic_year_id: number | null;
  terms: { id: number; name: string; academic_year_id: number; is_current?: boolean }[];
  current_term_id: number | null;
}

export interface ExamTrendPoint {
  exam_id: number;
  exam: string;
  mean: number;
  pass_rate?: number;
  delta_mean?: number | null;
}

export interface LessonPlanQueueFilters {
  classroom_id?: number;
  teacher_id?: number;
  date_from?: string;
  date_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface AcademicDashboardKpis {
  examsDraft: number;
  examsMarking: number;
  examsModeration: number;
  examsPublished: number;
  lessonPlansPendingReview: number;
  examStatusBreakdown: Record<string, number>;
  trendSummary: ExamTrendPoint[];
  trendClassroomId: number | null;
  trendTermId: number | null;
  trendAcademicYearId: number | null;
}

export interface ExamSummary {
  id: number;
  name: string;
  examTypeName: string | null;
  academicYearId: number | null;
  termId: number | null;
  classroomId: number | null;
  classroomName: string | null;
  streamId: number | null;
  subjectId: number | null;
  subjectName: string | null;
  startDate: string | null;
  endDate: string | null;
  status: ExamStatus;
  totalMarks: number;
}

export interface MarkRowSummary {
  id: number;
  studentId: number;
  studentName: string;
  marks: number;
  totalMarks: number;
  percentage: number;
  remarks: string | null;
}

export interface LessonPlanSummary {
  id: number;
  teacherName: string | null;
  subjectName: string | null;
  className: string | null;
  topic: string;
  plannedDate: string | null;
  submissionStatus: string;
  isLate: boolean;
}

/** `GET /reports/exams/class-sheet` — combined all-subjects results per exam/class. */
export interface ExamClassSheetSubject {
  id: number;
  name: string;
  code: string | null;
}

export interface ExamClassSheetRow {
  student_id: number;
  admission_number: string | null;
  name: string;
  stream_id: number | null;
  stream_name: string | null;
  subject_scores: Record<string, string | number | null>;
  subject_positions?: Record<string, number | null>;
  total: number | null;
  average: number | null;
  subjects_taken: number;
  position?: number | null;
  class_position?: number | null;
  stream_position?: number | null;
}

export interface ExamClassSheetRecord {
  meta: {
    mode: 'exam' | 'term';
    exam?: { id: number; name: string; academic_year_id: number | null; term_id: number | null };
    classroom: { id: number; name: string };
    stream_id: number | null;
    stream_name: string | null;
    subject_scoped?: boolean;
  };
  subjects: ExamClassSheetSubject[];
  rows: ExamClassSheetRow[];
}

/** `GET /reports/exams/my-teaching-scope` */
export interface TeachingScopeSlot {
  classroom_id: number;
  classroom_name: string | null;
  stream_id: number | null;
  stream_name: string | null;
  subject_id: number;
  subject_name: string | null;
  subject_code: string | null;
  category_key: string;
  category: string;
  teacher_id?: number | null;
  teacher_name?: string | null;
}

export interface TeachingScopePayload {
  full_access: boolean;
  slots: TeachingScopeSlot[];
}

/** `GET /reports/exams/my-subject-analysis` */
export interface SubjectAnalysisStudentRow {
  student_id: number;
  admission_number: string | null;
  name: string;
  stream_id: number | null;
  score: number | null;
  percentage: number | null;
  grade_label?: string | null;
  position?: number | null;
  prev_score?: number | null;
  improvement?: number | null;
  sitting_scores?: Record<string, { score: number | null; count: number }>;
}

export interface SubjectAnalysisPayload {
  meta: {
    mode: string;
    classroom: { id: number; name: string };
    subject: { id: number; name: string; code: string | null };
    stream_id: number | null;
    category?: string;
    previous_term?: {
      academic_year_id: number;
      term_id: number;
      term_name: string;
      academic_year: string | number | null;
    } | null;
    notice?: string | null;
    exam_session_name?: string | null;
    max_marks?: number;
  };
  summary: {
    count: number;
    mean: number | null;
    max: number | null;
    min: number | null;
    pass_rate: number | null;
    prev_mean: number | null;
    improvement: number | null;
  };
  sittings: Array<{ key: string; label: string; exam_type_id: number; session_id: number }>;
  students: SubjectAnalysisStudentRow[];
  most_improved_students?: SubjectAnalysisStudentRow[];
}

/** `GET /timetables/teacher/{staffId}` and `/timetables/student/{studentId}`. */
export interface TimetableSlotRecord {
  id: number;
  day: string;
  period?: number;
  start_time: string;
  end_time: string;
  subject_id: number;
  subject_name: string;
  teacher_id?: number | null;
  teacher_name?: string | null;
  room?: string | null;
  classroom_id?: number | null;
  classroom_name?: string | null;
  is_break?: boolean;
}

export interface TimetableRecord {
  class_name?: string | null;
  academic_year_id: number;
  term_id: number;
  slots: TimetableSlotRecord[];
}
