import type { AttendanceReportStatus, StudentSummary } from '@erp/core';
import type { Student360TabId } from '@erp/ui';

export type StudentsStackParamList = {
  StudentRegistry: undefined;
  ParentsContact: undefined;
  ArchivedStudents: undefined;
  AttendanceReport:
    | {
        date?: string;
        status?: AttendanceReportStatus | 'consecutive';
      }
    | undefined;
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
