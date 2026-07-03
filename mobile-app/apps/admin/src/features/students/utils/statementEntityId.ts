import type { StatementTransactionRecord } from '@erp/core';

/** Resolve real invoice/payment id from a statement row (API uses offset display ids). */
export function statementEntityId(tx: StatementTransactionRecord): number | null {
  if (tx.entity_id != null && tx.entity_id > 0) {
    return tx.entity_id;
  }
  if (tx.type === 'invoice' && tx.id >= 1_000_000 && tx.id < 2_000_000) {
    return tx.id - 1_000_000;
  }
  if (tx.type === 'payment' && tx.id >= 2_000_000 && tx.id < 3_000_000) {
    return tx.id - 2_000_000;
  }
  return null;
}
