import { useCan } from '@erp/core';
import { AlertCard, DashboardSection } from '@erp/ui';
import React from 'react';
import { ALERT_PLACEHOLDERS } from '../data/placeholders';

export const AlertsSection: React.FC = () => {
  const canViewAlerts = useCan(['dashboard.alerts.view', 'dashboard.view']);

  if (!canViewAlerts) {
    return null;
  }

  return (
    <DashboardSection
      title="Alerts"
      subtitle="Early warnings — placeholder feed"
    >
      {ALERT_PLACEHOLDERS.map((alert) => (
        <AlertCard
          key={alert.id}
          title={alert.title}
          message={alert.message}
          severity={alert.severity}
          timestamp={alert.timestamp}
          onPress={() => undefined}
        />
      ))}
    </DashboardSection>
  );
};
