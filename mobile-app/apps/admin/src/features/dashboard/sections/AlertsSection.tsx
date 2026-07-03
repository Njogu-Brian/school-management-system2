import {
  useApprovalList,
  useAcknowledgeSystemAlert,
  useCan,
  useSystemAlerts,
} from '@erp/core';
import { AlertCard, DashboardSection, EmptyState } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo } from 'react';
import { ActivityIndicator, Alert, View } from 'react-native';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';

type AlertRow = {
  id: string;
  title: string;
  message: string;
  severity: 'info' | 'success' | 'warning' | 'error';
  timestamp: string;
  onPress?: () => void;
};

function mapSystemSeverity(severity: string): AlertRow['severity'] {
  if (severity === 'critical' || severity === 'error') return 'error';
  if (severity === 'warning') return 'warning';
  if (severity === 'success') return 'success';
  return 'info';
}

export const AlertsSection: React.FC = () => {
  const canViewAlerts = useCan(['dashboard.alerts.view', 'dashboard.view']);
  const canViewApprovals = useCan(['approvals.view', 'dashboard.approvals.view', 'dashboard.view']);
  const canViewSystem = useCan(['dashboard.view']);
  const navigation = useNavigation();
  const acknowledge = useAcknowledgeSystemAlert();

  const approvalsQuery = useApprovalList({
    filters: { status: 'pending', priority: 'all', sourceType: 'all' },
    enabled: canViewAlerts && canViewApprovals,
    includeAdmissions: true,
  });

  const systemQuery = useSystemAlerts({ enabled: canViewAlerts && canViewSystem });

  const alerts = useMemo((): AlertRow[] => {
    const rows: AlertRow[] = [];

    for (const alert of systemQuery.data?.alerts ?? []) {
      rows.push(systemAlertRow(alert, () => {
        Alert.alert(alert.title, alert.body, [
          { text: 'Dismiss', style: 'cancel' },
          {
            text: 'Acknowledge',
            onPress: () => void acknowledge.mutateAsync(alert.id),
          },
        ]);
      }));
    }

    for (const item of (approvalsQuery.data ?? []).slice(0, 5)) {
      rows.push({
        id: `approval-${item.id}`,
        title: item.title,
        message: item.subtitle,
        severity:
          item.priority === 'critical' || item.priority === 'high' ? 'warning' : 'info',
        timestamp: new Date(item.requestedAt).toLocaleDateString('en-KE', {
          month: 'short',
          day: 'numeric',
        }),
        onPress: () =>
          navigateToDrawer(navigation, 'Approvals', 'ApprovalDetail', {
            id: item.id,
            item,
          }),
      });
    }

    return rows.slice(0, 12);
  }, [systemQuery.data, approvalsQuery.data, navigation, acknowledge]);

  if (!canViewAlerts) {
    return null;
  }

  const loading = approvalsQuery.isLoading || systemQuery.isLoading;

  return (
    <DashboardSection title="Alerts" subtitle="System alerts and pending approvals">
      {loading ? (
        <View style={{ paddingVertical: 16, alignItems: 'center' }}>
          <ActivityIndicator />
        </View>
      ) : null}

      {!loading && alerts.length === 0 ? (
        <EmptyState
          title="No active alerts"
          message="Critical system alerts and pending approvals will appear here."
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

function systemAlertRow(
  alert: { id: string; title: string; body: string; severity: string; created_at: string },
  onPress: () => void,
): AlertRow {
  return {
    id: `system-${alert.id}`,
    title: alert.title,
    message: alert.body,
    severity: mapSystemSeverity(alert.severity),
    timestamp: alert.created_at
      ? new Date(alert.created_at).toLocaleDateString('en-KE', { month: 'short', day: 'numeric' })
      : '',
    onPress,
  };
}
