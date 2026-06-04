import type { ApprovalItem } from '@erp/core';
import type { ApprovalDetailField } from '@erp/ui';
import { getSourceLabel } from '../registry/approvalRegistry';

function formatDate(value?: string): string {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleDateString('en-KE', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  } catch {
    return value;
  }
}

export function buildApprovalDetailFields(item: ApprovalItem): ApprovalDetailField[] {
  const fields: ApprovalDetailField[] = [
    { label: 'Type', value: getSourceLabel(item.sourceType) },
    { label: 'Requester', value: item.requesterName ?? '—' },
    { label: 'Requested', value: formatDate(item.requestedAt) },
    { label: 'Due / end', value: formatDate(item.dueDate) },
    { label: 'Reference', value: `#${item.sourceId}` },
  ];
  return fields;
}
