import type { SearchHit } from '@erp/core';
import { navigateToDrawer, navigateToTab, type WorkspaceNavigation } from '../../navigation/navigateWorkspace';

export function resolveSearchRoute(navigation: WorkspaceNavigation, hit: SearchHit): void {
  const route = hit.route ?? '';
  const meta = hit.metadata ?? {};
  const entityId = Number(meta.entity_id);

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
