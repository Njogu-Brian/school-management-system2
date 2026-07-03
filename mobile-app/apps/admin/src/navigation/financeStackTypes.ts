import type { InvoiceSummary, PaymentSummary, FinanceTransactionSummary } from '@erp/core';

export type FinanceStackParamList = {
  FinanceDashboard: undefined;
  BillingList: { hasBalance?: boolean } | undefined;
  InvoiceDetail: { invoiceId: number; summary?: InvoiceSummary };
  CollectionsList: { initialTab?: 'payments' | 'transactions'; transactionView?: string } | undefined;
  PaymentDetail: { paymentId: number; summary?: PaymentSummary };
  Statements: undefined;
  ReconciliationList: undefined;
  TransactionDetail: {
    transactionId: number;
    transactionType: 'bank' | 'c2b';
    summary?: FinanceTransactionSummary;
  };
};
