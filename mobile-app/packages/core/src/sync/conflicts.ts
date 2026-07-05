import { academicsWorkspaceApi } from '../api/academicsWorkspace.api';
import { attendanceApi } from '../api/attendance.api';
import type {
  AttendanceSyncPayload,
  ExamMarksMatrixSyncPayload,
  ExamMarksSyncPayload,
  SyncConflictRow,
} from './types';
import type { SyncQueueItem } from './syncQueue';
import { SYNC_KINDS } from './types';

export async function detectSyncConflicts(item: SyncQueueItem): Promise<SyncConflictRow[]> {
  switch (item.kind) {
    case SYNC_KINDS.ATTENDANCE_MARK:
      return detectAttendanceConflicts(item.payload as unknown as AttendanceSyncPayload);
    case SYNC_KINDS.EXAM_MARKS_BATCH:
      return detectExamMarksConflicts(item.payload as unknown as ExamMarksSyncPayload);
    case SYNC_KINDS.EXAM_MARKS_MATRIX:
      return detectExamMarksMatrixConflicts(item.payload as unknown as ExamMarksMatrixSyncPayload);
    default:
      return [];
  }
}

async function detectAttendanceConflicts(payload: AttendanceSyncPayload): Promise<SyncConflictRow[]> {
  const base = payload.baseSnapshot ?? {};
  const res = await attendanceApi.getClassAttendance({
    date: payload.date,
    class_id: payload.class_id,
    stream_id: payload.stream_id,
  });
  const serverMap = new Map((res.data ?? []).map((r) => [r.student_id, r.status]));
  const conflicts: SyncConflictRow[] = [];

  for (const record of payload.records) {
    const serverVal = serverMap.get(record.student_id) ?? 'unmarked';
    const baseVal = base[record.student_id] ?? 'unmarked';
    const localVal = record.status;
    if (serverVal !== baseVal && serverVal !== localVal) {
      conflicts.push({
        id: record.student_id,
        label: record.student_name ?? `Student #${record.student_id}`,
        serverValue: serverVal,
        localValue: localVal,
        baseValue: baseVal,
      });
    }
  }
  return conflicts;
}

async function detectExamMarksConflicts(payload: ExamMarksSyncPayload): Promise<SyncConflictRow[]> {
  const base = payload.baseSnapshot ?? {};
  const res = await academicsWorkspaceApi.listMarks({
    exam_id: payload.exam_id,
    subject_id: payload.subject_id,
    classroom_id: payload.classroom_id,
  });
  const serverMap = new Map(
    (res.data?.data ?? []).map((r) => [
      r.student_id,
      { marks: String(r.marks ?? ''), remarks: r.remarks ?? '' },
    ]),
  );
  const conflicts: SyncConflictRow[] = [];

  for (const row of payload.marks) {
    const server = serverMap.get(row.student_id) ?? { marks: '', remarks: '' };
    const baseRow = base[row.student_id] ?? { marks: '', remarks: '' };
    const localMarks = String(row.marks);
    const localRemarks = row.remarks ?? '';
    const serverMarks = server.marks;
    const serverRemarks = server.remarks;
    const marksChangedOnServer = serverMarks !== baseRow.marks;
    const remarksChangedOnServer = serverRemarks !== baseRow.remarks;
    const localDiffers =
      localMarks !== serverMarks || (localRemarks || '') !== (serverRemarks || '');
    if ((marksChangedOnServer || remarksChangedOnServer) && localDiffers) {
      conflicts.push({
        id: row.student_id,
        label: row.student_name ?? `Student #${row.student_id}`,
        serverValue: serverMarks ? `${serverMarks}${serverRemarks ? ` (${serverRemarks})` : ''}` : '—',
        localValue: `${localMarks}${localRemarks ? ` (${localRemarks})` : ''}`,
        baseValue: baseRow.marks ? `${baseRow.marks}${baseRow.remarks ? ` (${baseRow.remarks})` : ''}` : '—',
      });
    }
  }
  return conflicts;
}

async function detectExamMarksMatrixConflicts(
  payload: ExamMarksMatrixSyncPayload,
): Promise<SyncConflictRow[]> {
  const base = payload.baseSnapshot ?? {};
  const res = await academicsWorkspaceApi.getMarksMatrix({
    exam_type_id: payload.exam_type_id,
    classroom_id: payload.classroom_id,
    stream_id: payload.stream_id,
  });
  const serverMap = new Map<string, { marks: string; remarks: string }>();
  for (const m of res.data?.existing_marks ?? []) {
    serverMap.set(`${m.student_id}-${m.exam_id}`, {
      marks: m.marks == null ? '' : String(m.marks),
      remarks: m.remarks ?? '',
    });
  }

  const conflicts: SyncConflictRow[] = [];
  for (const entry of payload.entries) {
    const key = `${entry.student_id}-${entry.exam_id}`;
    const server = serverMap.get(key) ?? { marks: '', remarks: '' };
    const baseRow = base[key] ?? { marks: '', remarks: '' };
    const localMarks = entry.marks == null ? '' : String(entry.marks);
    const localRemarks = entry.remarks ?? '';
    const marksChangedOnServer = server.marks !== baseRow.marks;
    const remarksChangedOnServer = server.remarks !== baseRow.remarks;
    const localDiffers =
      localMarks !== server.marks || (localRemarks || '') !== (server.remarks || '');
    if ((marksChangedOnServer || remarksChangedOnServer) && localDiffers) {
      conflicts.push({
        id: key,
        label: `Student ${entry.student_id} · Exam ${entry.exam_id}`,
        serverValue: server.marks ? `${server.marks}${server.remarks ? ` (${server.remarks})` : ''}` : '—',
        localValue: localMarks ? `${localMarks}${localRemarks ? ` (${localRemarks})` : ''}` : '—',
        baseValue: baseRow.marks ? `${baseRow.marks}${baseRow.remarks ? ` (${baseRow.remarks})` : ''}` : '—',
      });
    }
  }
  return conflicts;
}
