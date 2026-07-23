/** Invoice list/detail records from `GET /invoices`. */
export type InvoiceStatus =
  | 'draft'
  | 'issued'
  | 'partially_paid'
  | 'paid'
  | 'overdue'
  | 'reversed';

export interface InvoiceListRecord {
  id: number;
  invoice_number: string;
  student_id: number;
  student_name: string | null;
  student_admission_number: string | null;
  term_id: number | null;
  academic_year_id: number | null;
  total_amount: number;
  paid_amount: number;
  balance: number;
  status: InvoiceStatus | string;
  due_date: string | null;
  issue_date: string;
  created_at: string;
  updated_at: string;
}

export interface InvoiceItemRecord {
  id: number;
  invoice_id: number;
  votehead_id: number;
  votehead_name: string;
  amount: number;
  quantity: number;
  total: number;
}

export interface InvoiceDetailRecord extends InvoiceListRecord {
  term_name: string | null;
  academic_year_name: string | null;
  notes: string | null;
  items: InvoiceItemRecord[];
}

export interface FeeStructureListRecord {
  id: number;
  name: string;
  class_id: number;
  class_name: string | null;
  term_id: number | null;
  academic_year_id: number | null;
  total_amount: number;
  status: 'active' | 'inactive' | string;
  created_at: string;
  updated_at: string;
}

export type PaymentMethodSlug =
  | 'cash'
  | 'mpesa'
  | 'bank_transfer'
  | 'cheque'
  | 'card'
  | 'stripe'
  | 'paypal'
  | string;

export type PaymentStatus = 'pending' | 'completed' | 'failed' | 'reversed' | string;

export interface PaymentListRecord {
  id: number;
  receipt_number: string;
  student_id: number;
  student_name: string | null;
  student_admission_number: string | null;
  amount: number;
  payment_method: PaymentMethodSlug;
  payment_date: string;
  reference_number: string | null;
  notes: string | null;
  status: PaymentStatus;
  unallocated_amount: number;
  created_at: string;
  updated_at: string;
}

export interface PaymentAllocationRecord {
  id: number;
  amount: number;
  invoice_id: number | null;
  invoice_number: string | null;
}

export interface PaymentDetailRecord extends PaymentListRecord {
  mpesa_receipt_number: string | null;
  allocated_amount: number;
  unallocated_amount: number;
  reversed: boolean;
  receipt_public_url: string | null;
  allocations: PaymentAllocationRecord[];
  class_name: string | null;
  stream_name: string | null;
  portal_note: string | null;
}

export type FinanceTransactionType = 'bank' | 'c2b';

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

export interface FinanceTransactionListRecord {
  id: number;
  transaction_type: FinanceTransactionType;
  trans_date: string | null;
  trans_amount: number | null;
  trans_code: string | null;
  description: string | null;
  bill_ref_number: string | null;
  phone_number: string | null;
  payer_name: string | null;
  student_id: number | null;
  student_name: string | null;
  match_status: string | null;
  match_confidence: number | null;
  status: string | null;
  is_duplicate: boolean;
  is_archived: boolean;
  payment_created: boolean;
  is_swimming_transaction: boolean;
  recorded_at: string | null;
}

export interface FinanceTransactionDetailRecord {
  id: number;
  transaction_type: FinanceTransactionType;
  transaction_date?: string | null;
  trans_time?: string | null;
  amount?: number;
  trans_amount?: number;
  reference_number?: string | null;
  trans_id?: string | null;
  description?: string | null;
  phone_number?: string | null;
  msisdn?: string | null;
  payer_name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  bill_ref_number?: string | null;
  student_id: number | null;
  student_name: string | null;
  match_status?: string | null;
  allocation_status?: string | null;
  match_confidence?: number | null;
  status?: string | null;
  is_shared?: boolean;
  shared_allocations?: unknown;
  payment_created?: boolean;
  payment_id?: number | null;
  is_duplicate?: boolean;
  is_archived?: boolean;
  is_swimming_transaction?: boolean;
  match_notes?: string | null;
  match_reason?: string | null;
}

export interface InvoiceListFilters {
  search?: string;
  status?: InvoiceStatus | string | null;
  student_id?: number;
  class_id?: number;
  stream_id?: number;
  year?: number;
  year_id?: number;
  term?: number;
  term_id?: number;
  include_reversed?: boolean;
  has_balance?: boolean;
  page?: number;
  per_page?: number;
}

export interface PaymentListFilters {
  search?: string;
  student_id?: number;
  class_id?: number;
  date_from?: string;
  date_to?: string;
  active_only?: boolean;
  page?: number;
  per_page?: number;
}

export interface FinanceTransactionListFilters {
  search?: string;
  view?: FinanceTransactionViewFilter | string;
  queue?: ReconciliationQueueFilter;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}

/** Workspace dashboard KPIs (composed client-side). */
export interface FinanceDashboardKpis {
  collectedToday: number;
  collectedThisMonth: number;
  outstandingFees: number;
  studentsInArrears: number;
  pendingReconciliation: number;
}

export interface InvoiceSummary {
  id: number;
  invoiceNumber: string;
  studentId: number;
  studentName: string | null;
  studentAdmissionNumber: string | null;
  totalAmount: number;
  balance: number;
  status: string;
  issueDate: string;
}

export interface PaymentSummary {
  id: number;
  receiptNumber: string;
  studentName: string | null;
  amount: number;
  paymentMethod: string;
  paymentDate: string;
  status: string;
}

export interface FinanceTransactionSummary {
  id: number;
  transactionType: FinanceTransactionType;
  reference: string | null;
  amount: number | null;
  studentName: string | null;
  status: string | null;
  transDate: string | null;
}
