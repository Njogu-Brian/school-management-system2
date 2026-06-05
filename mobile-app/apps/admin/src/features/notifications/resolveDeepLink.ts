import type { NotificationRecord } from '@erp/core';
import { navigateToDrawer, navigateToTab, type WorkspaceNavigation } from '../../navigation/navigateWorkspace';

export function resolveNotificationDeepLink(
  navigation: WorkspaceNavigation,
  notification: NotificationRecord,
): void {
  const link = notification.deep_link ?? '';
  const data = notification.data ?? {};
  const category = (notification.category ?? '').toLowerCase();

  if (link.includes('approval') || category === 'approvals') {
    navigateToDrawer(navigation, 'Approvals', 'ApprovalsHome');
    return;
  }
  if (link.includes('admission') || category === 'admissions') {
    const appId = data.application_id ?? data.admission_id;
    if (appId) {
      navigateToDrawer(navigation, 'Admissions', 'ApplicationDetail', { applicationId: Number(appId) });
    } else {
      navigateToDrawer(navigation, 'Admissions', 'AdmissionsWorkspace');
    }
    return;
  }
  if (link.includes('invoice') || link.includes('payment') || category === 'finance') {
    navigateToTab(navigation, 'Finance', 'BillingList');
    return;
  }
  if (link.includes('announcement') || category === 'communication') {
    navigateToDrawer(navigation, 'Communication', 'AnnouncementsList');
    return;
  }
  if (category === 'visitors' || category === 'operations') {
    navigateToDrawer(navigation, 'Operations', 'VisitorsList');
    return;
  }
  if (category === 'students') {
    navigateToTab(navigation, 'Students', 'StudentRegistry');
    return;
  }
  if (category === 'hr' || category === 'staff') {
    navigateToTab(navigation, 'People', 'StaffRegistry');
  }
}
