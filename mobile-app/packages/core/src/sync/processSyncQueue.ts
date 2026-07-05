import { academicsWorkspaceApi } from '../api/academicsWorkspace.api';
import { attendanceApi } from '../api/attendance.api';
import { financeApi } from '../api/finance.api';
import { detectSyncConflicts } from './conflicts';
import {
  getSyncItem,
  listSyncQueue,
  markSyncItemConflict,
  markSyncItemFailed,
  markSyncItemProcessing,
  removeSyncItem,
  resetSyncItem,
  type SyncQueueItem,
} from './syncQueue';
import type {
  AttendanceSyncPayload,
  ExamMarksMatrixSyncPayload,
  ExamMarksSyncPayload,
} from './types';
import { SYNC_KINDS } from './types';

export type SyncProcessorResult = {
  processed: number;
  failed: number;
  conflicts: number;
};

async function dispatchItem(item: SyncQueueItem): Promise<void> {
  switch (item.kind) {
    case SYNC_KINDS.FINANCE_ASSIGN: {
      const id = Number(item.payload.transactionId);
      const type = item.payload.type as 'bank' | 'c2b';
      const studentId = Number(item.payload.studentId);
      const res = await financeApi.assignTransaction(id, type, studentId);
      if (!res.success) throw new Error(res.message || 'Assign failed.');
      return;
    }
    case SYNC_KINDS.FINANCE_SHARE: {
      const id = Number(item.payload.transactionId);
      const type = item.payload.type as 'bank' | 'c2b';
      const allocations = item.payload.allocations as Array<{ student_id: number; amount: number }>;
      const res = await financeApi.shareTransaction(id, type, allocations);
      if (!res.success) throw new Error(res.message || 'Share failed.');
      return;
    }
    case SYNC_KINDS.ATTENDANCE_MARK: {
      const payload = item.payload as unknown as AttendanceSyncPayload;
      const res = await attendanceApi.mark({
        date: payload.date,
        class_id: payload.class_id,
        stream_id: payload.stream_id,
        records: payload.records.map((r) => ({
          student_id: r.student_id,
          status: r.status as 'present' | 'absent' | 'late' | 'unmarked',
        })),
      });
      if (!res.success) throw new Error(res.message || 'Attendance sync failed.');
      return;
    }
    case SYNC_KINDS.EXAM_MARKS_BATCH: {
      const payload = item.payload as unknown as ExamMarksSyncPayload;
      const res = await academicsWorkspaceApi.enterMarks({
        exam_id: payload.exam_id,
        subject_id: payload.subject_id,
        classroom_id: payload.classroom_id,
        marks: payload.marks,
      });
      if (!res.success) throw new Error(res.message || 'Marks sync failed.');
      return;
    }
    case SYNC_KINDS.EXAM_MARKS_MATRIX: {
      const payload = item.payload as unknown as ExamMarksMatrixSyncPayload;
      const res = await academicsWorkspaceApi.enterMarksMatrix({
        exam_type_id: payload.exam_type_id,
        classroom_id: payload.classroom_id,
        stream_id: payload.stream_id,
        entries: payload.entries,
      });
      if (!res.success) throw new Error(res.message || 'Matrix marks sync failed.');
      return;
    }
    default:
      throw new Error(`Unknown sync kind: ${item.kind}`);
  }
}

/** Drain pending queue items — call after NetInfo reconnect. */
export async function processSyncQueue(maxItems = 20): Promise<SyncProcessorResult> {
  const items = await listSyncQueue();
  const pending = items.filter(
    (i) =>
      i.status === 'pending' ||
      i.status === 'failed' ||
      (i.status === 'conflict' && i.forceApply === true),
  );
  let processed = 0;
  let failed = 0;
  let conflicts = 0;

  for (const item of pending.slice(0, maxItems)) {
    try {
      if (item.status === 'failed') {
        await resetSyncItem(item.id);
      }
      await markSyncItemProcessing(item.id);

      const current = (await getSyncItem(item.id)) ?? item;
      const force = current.forceApply === true;

      if (!force) {
        const detected = await detectSyncConflicts(current);
        if (detected.length > 0) {
          await markSyncItemConflict(current.id, detected);
          conflicts += 1;
          continue;
        }
      }

      await dispatchItem(current);
      await removeSyncItem(current.id);
      processed += 1;
    } catch (err) {
      failed += 1;
      await markSyncItemFailed(item.id, (err as Error).message);
    }
  }

  return { processed, failed, conflicts };
}

/** Re-run conflict detection for items stuck in conflict status. */
export async function retryConflictItems(): Promise<number> {
  const items = await listSyncQueue();
  const conflictItems = items.filter((i) => i.status === 'conflict');
  let resolved = 0;

  for (const item of conflictItems) {
    await resetSyncItem(item.id);
    resolved += 1;
  }

  if (resolved > 0) {
    await processSyncQueue();
  }
  return resolved;
}
