import { useApprovalList, useCan } from '@erp/core';
import { AlertCard, DashboardSection, EmptyState } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo } from 'react';
import { ActivityIndicator, View } from 'react-native';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';

export const AlertsSection: React.FC = () => {
  const canViewAlerts = useCan(['dashboard.alerts.view', 'dashboard.view']);
  const canViewApprovals = useCan(['approvals.view', 'dashboard.approvals.view', 'dashboard.view']);
  const navigation = useNavigation();

  const query = useApprovalList({
    filters: { status: 'pending', priority: 'all', sourceType: 'all' },
    enabled: canViewAlerts && canViewApprovals,
    includeAdmissions: true,
  });

  const alerts = useMemo(() => {
    const items = (query.data ?? []).slice(0, 5);
    return items.map((item) => ({
      id: item.id,
      title: item.title,
      message: item.subtitle,
      severity:
        item.priority === 'critical' || item.priority === 'high'
          ? ('warning' as const)
          : ('info' as const),
      timestamp: new Date(item.requestedAt).toLocaleDateString('en-KE', {
        month: 'short',
        day: 'numeric',
      }),
      onPress: () =>
        navigateToDrawer(navigation, 'Approvals', 'ApprovalDetail', {
          id: item.id,
          item,
        }),
    }));
  }, [query.data, navigation]);

  if (!canViewAlerts) {
    return null;
  }

  return (
    <DashboardSection title="Alerts" subtitle="Action items from live approval queues">
      {query.isLoading ? (
        <View style={{ paddingVertical: 16, alignItems: 'center' }}>
          <ActivityIndicator />
        </View>
      ) : null}

      {!query.isLoading && alerts.length === 0 ? (
        <EmptyState
          title="No active alerts"
          message="Pending approvals and system alerts will appear here when available."
          icon="notifications-outline"
        />
      ) : null}

      {alerts.map((alert) => (
        <AlertCard
          key={alert.id}
          title={alert.title}
          message={alert.message}
          severity={alert.severity}
          timestamp={alert.timestamp}
          onPress={alert.onPress}
        />
      ))}
    </DashboardSection>
  );
};
