import { useCan } from '@erp/core';
import { DashboardSection, EmptyState } from '@erp/ui';
import React from 'react';

export const OperationalStatusSection: React.FC = () => {
  const canView = useCan('dashboard.view');

  if (!canView) {
    return null;
  }

  return (
    <DashboardSection title="Operational status" subtitle="Platform & integrations">
      <EmptyState
        title="Status feed unavailable"
        message="SMS, M-Pesa, and backup health checks are not exposed via a mobile API yet. Use Settings → API diagnostics in development builds."
        icon="pulse-outline"
      />
    </DashboardSection>
  );
};
