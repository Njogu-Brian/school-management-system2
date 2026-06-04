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

  for (const tx of txs.slice(0, 6)) {
    events.push({
      id: `tx-${tx.id}`,
      title: tx.type === 'payment' ? 'Payment received' : 'Invoice issued',
      subtitle: tx.description || tx.reference,
      occurredAt: tx.date,
      kind: tx.type === 'payment' ? 'payment' : 'invoice',
    });
  }

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
