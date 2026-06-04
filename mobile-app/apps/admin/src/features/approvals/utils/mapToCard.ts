import type { ApprovalItem } from '@erp/core';
import type { ApprovalCardData } from '@erp/ui';
import { getSourceLabel } from '../registry/approvalRegistry';

function formatRequestedAt(iso: string): string | undefined {
  try {
    return `Requested ${new Date(iso).toLocaleString('en-KE', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })}`;
  } catch {
    return undefined;
  }
}

export function approvalItemToCard(
  item: ApprovalItem,
  onPress?: () => void,
): ApprovalCardData {
  const requestedAtLabel = formatRequestedAt(item.requestedAt);

  return {
    id: item.id,
    title: item.title,
    subtitle: item.subtitle,
    status: item.status,
    priority: item.priority,
    sourceLabel: getSourceLabel(item.sourceType),
    requestedAtLabel,
    onPress,
  };
}
