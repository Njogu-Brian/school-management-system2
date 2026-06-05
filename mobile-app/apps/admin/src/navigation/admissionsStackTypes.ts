import type { ApplicationSummary } from '@erp/core';

export type AdmissionsStackParamList = {
  AdmissionsWorkspace: undefined;
  ApplicationDetail: {
    applicationId: number;
    summary?: ApplicationSummary;
  };
};
