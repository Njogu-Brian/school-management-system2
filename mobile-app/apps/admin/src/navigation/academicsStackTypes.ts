import type { AssessmentHistoryItem, ExamSummary, LessonPlanSummary } from '@erp/core';

export type AcademicsStackParamList = {
  AcademicsDashboard: undefined;
  Assessments: undefined;
  AssessmentHistory: { studentId: number; studentName: string };
  AssessmentDetail: { item: AssessmentHistoryItem; studentName: string };
  ExamsList: undefined;
  ExamDetail: { examId: number; summary?: ExamSummary };
  ExamClassSheet: {
    examId?: number;
    examSessionId?: number;
    classroomId: number;
    streamId?: number;
    title?: string;
  };
  MarksEntry: {
    examId: number;
    classroomId: number;
    subjectId: number;
    classroomName: string;
    subjectName: string;
  };
  Marks: undefined;
  MarksMatrix: undefined;
  MarksMatrixSetup: undefined;
  MarksMatrixEntry: { examTypeId: number; classroomId: number; streamId?: number };
  ReportCards: undefined;
  ReportCardHistory: { studentId: number; studentName: string };
  ReportCardDetail: { reportCardId: number; studentName: string };
  Moderation: undefined;
  LessonPlanReview: { lessonPlanId: number; summary?: LessonPlanSummary };
  CbcCurriculum: undefined;
  CbcStrands: { learningAreaId: number; learningAreaName?: string };
  CbcSubstrand: { substrandId: number; substrandName?: string };
  MarkAttendance: undefined;
};
