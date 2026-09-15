export type AttendanceReasonDraft = {
  reason_code_id?: number | null;
  reason?: string | null;
  excuse_notes?: string | null;
};

export function attendanceReasonLabel(
  draft?: AttendanceReasonDraft | null,
  codes?: Array<{ id: number; name: string }>,
): string {
  if (!draft) return '';
  const notes = (draft.excuse_notes || draft.reason || '').trim();
  const named = draft.reason_code_id
    ? codes?.find((code) => code.id === draft.reason_code_id)?.name
    : undefined;
  if (named && notes && notes !== named) return `${named} — ${notes}`;
  return named || notes;
}

export function attendanceReasonKey(draft?: AttendanceReasonDraft | null): string {
  return `${draft?.reason_code_id ?? ''}|${(draft?.excuse_notes || draft?.reason || '').trim()}`;
}
