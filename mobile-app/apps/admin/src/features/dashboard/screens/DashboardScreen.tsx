import { getNavArea, useCan } from '@erp/core';
import { PlaceholderScreen } from '@erp/ui';
import React from 'react';
import { DashboardLayout } from '../components/DashboardLayout';

const area = getNavArea('dashboard');

/**
 * Admin dashboard — School Command Center (Sprint 2 Batch 1).
 * Overview tab content uses the composable widget framework; Approvals/Alerts
 * tabs remain placeholders until later batches.
 */
export const DashboardScreen: React.FC = () => {
  const canView = useCan('dashboard.view');

  if (!canView) {
    return (
      <PlaceholderScreen
        title={area.label}
        description="You need dashboard.view permission to open the command center."
        icon="lock-closed-outline"
      />
    );
  }

  return <DashboardLayout />;
};
