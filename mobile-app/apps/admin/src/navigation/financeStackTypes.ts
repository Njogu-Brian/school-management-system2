import type { InvoiceSummary, PaymentSummary, FinanceTransactionSummary } from '@erp/core';

export type FinanceStackParamList = {
  FinanceDashboard: undefined;
  BillingList: undefined;
  InvoiceDetail: { invoiceId: number; summary?: InvoiceSummary };
  CollectionsList: undefined;
  PaymentDetail: { paymentId: number; summary?: PaymentSummary };
  Statements: undefined;
  ReconciliationList: undefined;
  TransactionDetail: {
    transactionId: number;
    transactionType: 'bank' | 'c2b';
    summary?: FinanceTransactionSummary;
  };
};
