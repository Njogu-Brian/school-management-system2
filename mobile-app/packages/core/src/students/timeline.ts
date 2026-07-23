import type { StudentTimelineEvent } from '../types/student360';
import type { StudentStatementRecord } from '../types/student360';

export function buildStudentTimeline(input: {
  statement?: StudentStatementRecord | null;
  admissionDate?: string | null;
  updatedAt?: string | null;
  limit?: number;
}): StudentTimelineEvent[] {
  const events: StudentTimelineEvent[] = [];
  const limit = input.limit ?? 8;

  const txs = [...(input.statement?.transactions ?? [])].sort((a, b) =>
    b.date.localeCompare(a.date),
  );

  txs.slice(0, 6).forEach((tx, index) => {
    const entity = tx.entity_id ?? tx.id;
    events.push({
      // Include index + entity so duplicate invoice lines never share a React key.
      id: `tx-${tx.type}-${entity}-${tx.date}-${index}`,
      title: tx.type === 'payment' || tx.type === 'Payment' ? 'Payment received' : 'Invoice issued',
      subtitle: tx.description || tx.reference,
      occurredAt: tx.date,
      kind: tx.type === 'payment' || tx.type === 'Payment' ? 'payment' : 'invoice',
    });
  });

  if (input.admissionDate) {
    events.push({
      id: 'admission',
      title: 'Admission',
      subtitle: 'Student enrolled',
      occurredAt: input.admissionDate,
      kind: 'enrollment',
    });
  }

  if (input.updatedAt && input.updatedAt !== input.admissionDate) {
    events.push({
      id: 'profile-update',
      title: 'Profile updated',
      subtitle: 'Student record changed',
      occurredAt: input.updatedAt,
      kind: 'update',
    });
  }

  return events
    .sort((a, b) => b.occurredAt.localeCompare(a.occurredAt))
    .slice(0, limit);
}
