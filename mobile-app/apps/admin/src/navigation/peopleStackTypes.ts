import type { StaffSummary } from '@erp/core';

export type PeopleStackParamList = {
  StaffRegistry: undefined;
  StaffDetail: {
    staffId: number;
    summary?: StaffSummary;
  };
  PerformanceReviewDetail: { staffId: number; reviewId: number };
  TrainingRecordDetail: { staffId: number; recordId: number };
  StaffClock: undefined;
};
