export type InvoiceStatusFilter = 'all' | 'issued' | 'partially_paid' | 'paid' | 'overdue';

export type ReconciliationQueueFilter = 'pending' | 'confirmed' | 'rejected';

export type FinanceTransactionViewFilter =
  | 'all'
  | 'auto-assigned'
  | 'unassigned'
  | 'duplicate'
  | 'swimming'
  | 'manual-assigned'
  | 'draft'
  | 'collected'
  | 'archived';
