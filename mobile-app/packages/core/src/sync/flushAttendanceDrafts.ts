import { clearDraft, listDraftKeys, loadDraft, parseAttendanceDraftKey } from './draftStorage';
import { enqueueSyncItem } from './syncQueue';
import { SYNC_KINDS } from './types';

export type AttendanceDraftPayload = {
  statusById: Record<number, string>;
  reasonById?: Record<number, { reason_code_id?: number | null; reason?: string | null; excuse_notes?: string | null }>;
  serverSnapshot?: Record<number, string>;
};

function snapshotStatus(snapshot: Record<number, string>, studentId: number): string {
  return snapshot[studentId] ?? (snapshot as Record<string, string>)[String(studentId)] ?? 'unmarked';
}

/**
 * Move unsubmitted attendance drafts off the device into the sync queue.
 * Kept for manual recovery — Mark Attendance now submits only after confirm,
 * so OfflineShell no longer calls this automatically.
 */
export async function enqueueAttendanceDrafts(): Promise<number> {
  const keys = await listDraftKeys();
  let enqueued = 0;

  for (const key of keys) {
    const parsed = parseAttendanceDraftKey(key);
    if (!parsed) continue;

    const draft = await loadDraft<AttendanceDraftPayload>(key);
    if (!draft?.statusById) {
      await clearDraft(key);
      continue;
    }

    const snapshot = draft.serverSnapshot ?? {};
    const records = Object.entries(draft.statusById)
      .map(([id, status]) => {
        const studentId = Number(id);
        const reason = draft.reasonById?.[studentId];
        return {
          student_id: studentId,
          status: String(status),
          reason_code_id: reason?.reason_code_id,
          reason: reason?.reason,
          excuse_notes: reason?.excuse_notes,
        };
      })
      .filter(
        (row) => Number.isFinite(row.student_id) && row.status !== snapshotStatus(snapshot, row.student_id),
      );

    if (records.length === 0) {
      await clearDraft(key);
      continue;
    }

    await enqueueSyncItem(
      SYNC_KINDS.ATTENDANCE_MARK,
      {
        date: parsed.date,
        class_id: parsed.classId,
        stream_id: parsed.streamId,
        records,
        baseSnapshot: snapshot,
      },
      { label: `Attendance · Class #${parsed.classId} · ${parsed.date}` },
    );
    await clearDraft(key);
    enqueued += 1;
  }

  return enqueued;
}
