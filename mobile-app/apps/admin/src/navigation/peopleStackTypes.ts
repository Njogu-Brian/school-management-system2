import type { StaffSummary } from '@erp/core';

export type PeopleStackParamList = {
  StaffRegistry: undefined;
  StaffDetail: {
    staffId: number;
    summary?: StaffSummary;
  };
};
