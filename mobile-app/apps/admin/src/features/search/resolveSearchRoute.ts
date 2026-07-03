import type { SearchHit } from '@erp/core';
import type { AdminAreaKey } from '@erp/core';
import { AREA_TO_DRAWER_ROUTE } from '../../navigation/areaRoutes';
import { navigateToDrawer, navigateToTab, type WorkspaceNavigation } from '../../navigation/navigateWorkspace';

const MENU_AREA: Record<string, AdminAreaKey> = {
  dashboard: 'dashboard',
  approvals: 'approvals',
  admissions: 'admissions',
  students: 'students',
  academics: 'academics',
  finance: 'finance',
  people: 'people',
  operations: 'operations',
  communication: 'communication',
  reports: 'reports',
  settings: 'settings',
};

export function resolveSearchRoute(navigation: WorkspaceNavigation, hit: SearchHit): void {
  const route = hit.route ?? '';
  const meta = hit.metadata ?? {};
  const entityId = Number(meta.entity_id);

  if (meta.entity_type === 'menu') {
    const area = MENU_AREA[String(meta.entity_id)] as AdminAreaKey | undefined;
    if (!area) return;
    if (area === 'dashboard') {
      navigateToTab(navigation, 'Dashboard', 'DashboardHome');
    } else if (area === 'students') {
      navigateToTab(navigation, 'Students', 'StudentRegistry');
    } else if (area === 'finance') {
      navigateToTab(navigation, 'Finance', 'FinanceDashboard');
    } else if (area === 'people') {
      navigateToTab(navigation, 'People', 'StaffRegistry');
    } else {
      const drawer = AREA_TO_DRAWER_ROUTE[area];
      if (drawer) navigateToDrawer(navigation, drawer);
    }
    return;
  }

  if (route.startsWith('students/') && entityId) {
    navigateToTab(navigation, 'Students', 'StudentDetail', { studentId: entityId });
    return;
  }
  if (route.startsWith('people/') && entityId) {
    navigateToTab(navigation, 'People', 'StaffDetail', { staffId: entityId });
    return;
  }
  if (route.startsWith('admissions/') && entityId) {
    navigateToDrawer(navigation, 'Admissions', 'ApplicationDetail', { applicationId: entityId });
    return;
  }
  if (route.startsWith('finance/invoices/') && entityId) {
    navigateToTab(navigation, 'Finance', 'InvoiceDetail', { invoiceId: entityId });
    return;
  }
  if (route.startsWith('finance/payments/') && entityId) {
    navigateToTab(navigation, 'Finance', 'PaymentDetail', { paymentId: entityId });
    return;
  }
  if (route.startsWith('operations/visitors/') && entityId) {
    navigateToDrawer(navigation, 'Operations', 'VisitorDetail', { visitorId: entityId });
    return;
  }
  if (route.startsWith('operations/assets/') && entityId) {
    navigateToDrawer(navigation, 'Operations', 'AssetDetail', { assetId: entityId });
    return;
  }
  if (route.startsWith('operations/transport/vehicles/') && entityId) {
    navigateToDrawer(navigation, 'Operations', 'VehicleForm', { vehicleId: entityId });
    return;
  }
  if (route.startsWith('operations/requisitions/') && entityId) {
    navigateToDrawer(navigation, 'Operations', 'RequisitionDetail', { requisitionId: entityId });
    return;
  }
  if (route.startsWith('operations/inventory/')) {
    navigateToDrawer(navigation, 'Operations', 'InventoryList');
    return;
  }
  if (route.startsWith('communication/announcements/') && entityId) {
    navigateToDrawer(navigation, 'Communication', 'AnnouncementDetail', { announcementId: entityId });
  }
}
