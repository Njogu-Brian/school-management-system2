import type { StudentSummary } from '@erp/core';

export type StudentsStackParamList = {
  StudentRegistry: undefined;
  StudentDetail: {
    studentId: number;
    summary?: StudentSummary;
  };
  ReportCardDetail: {
    reportCardId: number;
    studentName?: string;
  };
};
