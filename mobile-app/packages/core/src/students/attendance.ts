import type {
  AttendanceCalendarDay,
  AttendanceSummary,
  AttendanceTrendPoint,
} from '../types/student360';

export function summarizeAttendanceDays(
  days: AttendanceCalendarDay[],
  attendancePercentage?: number | null,
): AttendanceSummary {
  let present = 0;
  let absent = 0;
  let late = 0;
  let excused = 0;

  for (const day of days) {
    const status = (day.status ?? '').toLowerCase();
    if (status === 'present') present += 1;
    else if (status === 'absent') absent += 1;
    else if (status === 'late') late += 1;
    if (day.is_excused) excused += 1;
  }

  const marked = present + absent + late;
  const percentage =
    attendancePercentage != null
      ? attendancePercentage
      : marked > 0
        ? Math.round(((present + late) / marked) * 1000) / 10
        : null;

  return { present, absent, late, excused, marked, percentage };
}

/** Build simple weekly trend from calendar days (last ≤8 weeks with data). */
export function buildAttendanceTrend(
  monthBuckets: AttendanceCalendarDay[][],
): AttendanceTrendPoint[] {
  const all = monthBuckets.flat();
  if (all.length === 0) return [];

  const byWeek = new Map<string, AttendanceCalendarDay[]>();
  for (const day of all) {
    const d = new Date(day.date);
    if (Number.isNaN(d.getTime())) continue;
    const weekStart = new Date(d);
    weekStart.setDate(d.getDate() - d.getDay());
    const key = weekStart.toISOString().slice(0, 10);
    const list = byWeek.get(key) ?? [];
    list.push(day);
    byWeek.set(key, list);
  }

  const sorted = [...byWeek.entries()].sort(([a], [b]) => a.localeCompare(b)).slice(-8);

  return sorted.map(([key, days]) => {
    const s = summarizeAttendanceDays(days);
    const label = new Date(key).toLocaleDateString('en-KE', { month: 'short', day: 'numeric' });
    return { label, present: s.present, absent: s.absent, late: s.late };
  });
}
