import type { StaffSummary } from '@erp/core';

export type PeopleStackParamList = {
  StaffRegistry: undefined;
  StaffDetail: {
    staffId: number;
    summary?: StaffSummary;
  };
  StaffEdit: { staffId: number };
  LeaveApply: { staffId?: number } | undefined;
  LeaveManagement: undefined;
  LeaveTypes: undefined;
  StaffAdvances: undefined;
  PayrollRecords: undefined;
  PerformanceReviewDetail: { staffId: number; reviewId: number };
  TrainingRecordDetail: { staffId: number; recordId: number };
  StaffClock: undefined;
  StaffClockTeam: undefined;
};
