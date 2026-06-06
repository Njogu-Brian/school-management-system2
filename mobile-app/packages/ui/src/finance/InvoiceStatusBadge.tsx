import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { SemanticTone } from '../theme/tokens';

export function invoiceStatusLabel(status: string): string {
  switch (status) {
    case 'issued':
      return 'Issued';
    case 'partially_paid':
      return 'Partial';
    case 'paid':
      return 'Paid';
    case 'overdue':
      return 'Overdue';
    case 'reversed':
      return 'Reversed';
    default:
      return status;
  }
}

function invoiceTone(status: string): SemanticTone {
  if (status === 'paid') return 'success';
  if (status === 'partially_paid' || status === 'issued') return 'warning';
  if (status === 'overdue') return 'danger';
  return 'brand';
}

export interface InvoiceStatusBadgeProps {
  status: string;
}

export const InvoiceStatusBadge: React.FC<InvoiceStatusBadgeProps> = ({ status }) => {
  const label = invoiceStatusLabel(status);
  return <StatusBadge label={label} tone={invoiceTone(status)} compact />;
};
