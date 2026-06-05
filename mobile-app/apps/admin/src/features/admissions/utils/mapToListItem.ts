import type { ApplicationSummary } from '@erp/core';
import type { ApplicationListItemData } from '@erp/ui';

export function summaryToListItem(summary: ApplicationSummary): ApplicationListItemData {
  return {
    id: summary.id,
    fullName: summary.fullName,
    applicationStatus: summary.applicationStatus,
    applicationDate: summary.applicationDate,
    preferredClassName: summary.preferredClassName,
    className: summary.className,
    waitlistPosition: summary.waitlistPosition,
    avatarUrl: summary.passportPhotoUrl,
  };
}
