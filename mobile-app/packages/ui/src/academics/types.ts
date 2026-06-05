export interface AcademicKpiCardProps {
  label: string;
  value: string;
  icon?: keyof typeof import('@expo/vector-icons').Ionicons.glyphMap;
}

export interface ExamListItemData {
  id: number;
  name: string;
  status: string;
  examTypeName: string | null;
  classroomName: string | null;
  subjectName: string | null;
  startDate: string | null;
  onPress?: () => void;
}

export interface MarksRowData {
  studentName: string;
  marks: number;
  totalMarks: number;
  percentage: number;
  remarks: string | null;
}

export interface ReportCardCardData {
  id: number;
  termLabel: string;
  status: string;
  overallGrade?: string | null;
  overallPercentage?: number;
  onPress?: () => void;
}

export interface AssessmentCardItem {
  id: string;
  typeLabel: string;
  title: string;
  subjectName: string | null;
  assessedOn: string | null;
  scoreDisplay: string | null;
  scorePercent: number | null;
  gradeLabel: string | null;
  performanceLevel: { name: string } | null;
  status: string;
  remark: string | null;
  legacySource: { table: string; id: number };
}

export interface AssessmentCardData {
  item: AssessmentCardItem;
  onPress?: () => void;
}

export interface LessonPlanSummaryUi {
  id: number;
  teacherName: string | null;
  subjectName: string | null;
  className: string | null;
  topic: string;
  plannedDate: string | null;
  submissionStatus: string;
  isLate: boolean;
}

export interface ModerationCardData {
  plan: LessonPlanSummaryUi;
  onPress?: () => void;
}

export interface AcademicTrendCardProps {
  examName: string;
  mean: number;
  passRate?: number;
}

