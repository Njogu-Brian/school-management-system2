import type {
  FinanceTransactionListRecord,
  FinanceTransactionSummary,
  InvoiceDetailRecord,
  InvoiceListRecord,
  InvoiceSummary,
  PaymentDetailRecord,
  PaymentListRecord,
  PaymentSummary,
  ReconciliationQueueFilter,
} from '../types/finance';

export function normalizeInvoiceSummary(row: InvoiceListRecord): InvoiceSummary {
  return {
    id: row.id,
    invoiceNumber: row.invoice_number,
    studentId: row.student_id,
    studentName: row.student_name,
    studentAdmissionNumber: row.student_admission_number,
    totalAmount: row.total_amount,
    balance: row.balance,
    status: row.status,
    issueDate: row.issue_date,
  };
}

export function normalizePaymentSummary(row: PaymentListRecord): PaymentSummary {
  return {
    id: row.id,
    receiptNumber: row.receipt_number,
    studentName: row.student_name,
    amount: row.amount,
    paymentMethod: row.payment_method,
    paymentDate: row.payment_date,
    status: row.status,
  };
}

export function normalizeFinanceTransactionSummary(
  row: FinanceTransactionListRecord,
): FinanceTransactionSummary {
  return {
    id: row.id,
    transactionType: row.transaction_type,
    reference: row.trans_code ?? row.bill_ref_number,
    amount: row.trans_amount,
    studentName: row.student_name,
    status: row.status ?? row.match_status,
    transDate: row.trans_date,
  };
}

export function invoiceStatusLabel(status: string): string {
  switch (status) {
    case 'issued':
      return 'Issued';
    case 'partially_paid':
      return 'Partially paid';
    case 'paid':
      return 'Paid';
    case 'overdue':
      return 'Overdue';
    case 'reversed':
      return 'Reversed';
    case 'draft':
      return 'Draft';
    default:
      return status;
  }
}

export function paymentMethodLabel(method: string): string {
  switch (method?.toLowerCase()) {
    case 'cash':
      return 'Cash';
    case 'mpesa':
      return 'M-Pesa';
    case 'bank_transfer':
      return 'Bank transfer';
    case 'cheque':
      return 'Cheque';
    case 'card':
    case 'stripe':
      return 'Card';
    default:
      return method?.replace(/_/g, ' ') ?? '—';
  }
}

export function reconciliationQueueToView(queue: ReconciliationQueueFilter): string {
  switch (queue) {
    case 'pending':
      return 'unassigned';
    case 'confirmed':
      return 'collected';
    case 'rejected':
      return 'archived';
    default:
      return 'all';
  }
}

export function reconciliationQueueLabel(queue: ReconciliationQueueFilter): string {
  switch (queue) {
    case 'pending':
      return 'Pending';
    case 'confirmed':
      return 'Confirmed';
    case 'rejected':
      return 'Rejected';
    default:
      return queue;
  }
}

export function formatFinanceAmount(amount: number | null | undefined): string {
  if (amount == null || Number.isNaN(amount)) return '—';
  return `KES ${amount.toLocaleString('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
}

export type { InvoiceDetailRecord, PaymentDetailRecord };
