import type { StudentSummary } from '@erp/core';
import type { Student360TabId } from '@erp/ui';

export type StudentsStackParamList = {
  StudentRegistry: undefined;
  StudentDetail: {
    studentId: number;
    summary?: StudentSummary;
    tab?: Student360TabId;
  };
  ReportCardDetail: {
    reportCardId: number;
    studentName?: string;
  };
  MedicalRecordForm: {
    studentId: number;
    studentName?: string;
  };
  StudentEdit: { studentId: number };
};
