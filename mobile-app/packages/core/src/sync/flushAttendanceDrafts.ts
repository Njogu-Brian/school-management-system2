import { clearDraft, listDraftKeys, loadDraft, parseAttendanceDraftKey } from './draftStorage';
import { enqueueSyncItem } from './syncQueue';
import { SYNC_KINDS } from './types';

export type AttendanceDraftPayload = {
  statusById: Record<number, string>;
  serverSnapshot?: Record<number, string>;
};

function snapshotStatus(snapshot: Record<number, string>, studentId: number): string {
  return snapshot[studentId] ?? (snapshot as Record<string, string>)[String(studentId)] ?? 'unmarked';
}

/**
 * Move unsubmitted attendance drafts off the device into the sync queue.
 * The server cannot reach into phones — this runs the next time the teacher
 * opens the app while logged in. Callers should then drain `processSyncQueue`.
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
      .map(([id, status]) => ({
        student_id: Number(id),
        status: String(status),
      }))
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
