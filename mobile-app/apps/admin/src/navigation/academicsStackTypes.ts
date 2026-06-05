import type { AssessmentHistoryItem, ExamSummary, LessonPlanSummary } from '@erp/core';

export type AcademicsStackParamList = {
  AcademicsDashboard: undefined;
  Assessments: undefined;
  AssessmentHistory: { studentId: number; studentName: string };
  AssessmentDetail: { item: AssessmentHistoryItem; studentName: string };
  ExamsList: undefined;
  ExamDetail: { examId: number; summary?: ExamSummary };
  Marks: undefined;
  MarksMatrix: undefined;
  ReportCards: undefined;
  ReportCardHistory: { studentId: number; studentName: string };
  ReportCardDetail: { reportCardId: number; studentName: string };
  Moderation: undefined;
  LessonPlanReview: { lessonPlanId: number; summary?: LessonPlanSummary };
};
