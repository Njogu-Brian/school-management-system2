import { admissionsApi } from './admissions.api';
import { academicsApi } from './academics.api';
import { dashboardApi } from './dashboard.api';
import { settingsApi } from './settings.api';
import { staffApi } from './staff.api';
import { studentsApi } from './students.api';
import type { ApiError } from '../types/api';

export interface ApiProbeResult {
  group: string;
  label: string;
  endpoint: string;
  ok: boolean;
  status?: number;
  message: string;
  durationMs: number;
}

async function probe(
  group: string,
  label: string,
  endpoint: string,
  run: () => Promise<unknown>,
): Promise<ApiProbeResult> {
  const start = Date.now();
  try {
    const res = (await run()) as { success?: boolean; message?: string };
    const durationMs = Date.now() - start;
    if (res && typeof res === 'object' && res.success === false) {
      return {
        group,
        label,
        endpoint,
        ok: false,
        message: res.message || 'success=false',
        durationMs,
      };
    }
    return { group, label, endpoint, ok: true, status: 200, message: 'OK', durationMs };
  } catch (err) {
    const apiErr = err as ApiError;
    return {
      group,
      label,
      endpoint,
      ok: false,
      status: apiErr.status,
      message: apiErr.message || 'Request failed',
      durationMs: Date.now() - start,
    };
  }
}

export interface ApiDiagnosticsContext {
  studentId?: number;
  staffId?: number;
}

/** Development-only ERP health probes for Settings, Students, Staff, and Dashboard APIs. */
export async function runApiDiagnostics(
  context: ApiDiagnosticsContext = {},
): Promise<ApiProbeResult[]> {
  let studentId = context.studentId;
  let staffId = context.staffId;

  if (!studentId) {
    try {
      const list = await studentsApi.list({ per_page: 1 });
      studentId = list.data?.data?.[0]?.id;
    } catch {
      // Probes that need studentId will report missing context below.
    }
  }

  if (!staffId) {
    try {
      const list = await staffApi.list({ per_page: 1 });
      staffId = list.data?.data?.[0]?.id;
    } catch {
      // Probes that need staffId will report missing context below.
    }
  }

  const probes: Array<() => Promise<ApiProbeResult>> = [
    () => probe('Dashboard', 'Stats', 'GET /dashboard/stats', () => dashboardApi.getStats()),
    () => probe('Admissions', 'Stats', 'GET /admissions/stats', () => admissionsApi.getStats()),
    () =>
      probe('Admissions', 'List', 'GET /admissions', () => admissionsApi.list({ per_page: 5 })),
    () =>
      probe('Settings', 'School', 'GET /settings/school', () => settingsApi.getSchool()),
    () =>
      probe('Settings', 'Academic years', 'GET /settings/academic-years', () =>
        settingsApi.getAcademicYears(),
      ),
    () =>
      probe('Settings', 'Terms', 'GET /settings/terms', () => settingsApi.getTerms()),
    () =>
      probe('Settings', 'Classes', 'GET /settings/classes', () => settingsApi.getClasses()),
    () =>
      probe('Settings', 'Subjects', 'GET /settings/subjects', () => settingsApi.getSubjects()),
    () =>
      probe('Settings', 'Grading', 'GET /settings/grading', () => settingsApi.getGrading()),
    () =>
      probe('Settings', 'Roles', 'GET /settings/roles', () => settingsApi.getRoles()),
    () =>
      studentId
        ? probe('Students', 'Detail', `GET /students/${studentId}`, () =>
            studentsApi.getById(studentId as number),
          )
        : Promise.resolve({
            group: 'Students',
            label: 'Detail',
            endpoint: 'GET /students/{id}',
            ok: false,
            message: 'No student id (list failed or empty)',
            durationMs: 0,
          }),
    () =>
      studentId
        ? probe('Students', 'Academic summary', `GET /students/${studentId}/academic-summary`, () =>
            academicsApi.getAcademicSummary(studentId as number),
          )
        : Promise.resolve({
            group: 'Students',
            label: 'Academic summary',
            endpoint: 'GET /students/{id}/academic-summary',
            ok: false,
            message: 'No student id',
            durationMs: 0,
          }),
    () =>
      studentId
        ? probe(
            'Students',
            'Assessment history',
            `GET /students/${studentId}/assessment-history`,
            () => academicsApi.getAssessmentHistory(studentId as number, { page: 1, per_page: 5 }),
          )
        : Promise.resolve({
            group: 'Students',
            label: 'Assessment history',
            endpoint: 'GET /students/{id}/assessment-history',
            ok: false,
            message: 'No student id',
            durationMs: 0,
          }),
    () =>
      studentId
        ? probe('Assessment', 'Report cards', `GET /report-cards?student_id=${studentId}`, () =>
            academicsApi.getReportCards(studentId as number, { per_page: 5 }),
          )
        : Promise.resolve({
            group: 'Assessment',
            label: 'Report cards',
            endpoint: 'GET /report-cards?student_id=',
            ok: false,
            message: 'No student id',
            durationMs: 0,
          }),
    () =>
      staffId
        ? probe('Staff', 'Detail', `GET /staff/${staffId}`, () =>
            staffApi.getById(staffId as number),
          )
        : Promise.resolve({
            group: 'Staff',
            label: 'Detail',
            endpoint: 'GET /staff/{id}',
            ok: false,
            message: 'No staff id (list failed or empty)',
            durationMs: 0,
          }),
    () =>
      staffId
        ? probe(
            'Staff',
            'Leave balances',
            `GET /staff/${staffId}/leave-balances`,
            () => staffApi.leaveBalances(staffId as number),
          )
        : Promise.resolve({
            group: 'Staff',
            label: 'Leave balances',
            endpoint: 'GET /staff/{id}/leave-balances',
            ok: false,
            message: 'No staff id',
            durationMs: 0,
          }),
    () =>
      staffId
        ? probe(
            'Staff',
            'Attendance history',
            `GET /staff/${staffId}/attendance-history`,
            () => staffApi.attendanceHistory(staffId as number, { per_page: 5 }),
          )
        : Promise.resolve({
            group: 'Staff',
            label: 'Attendance history',
            endpoint: 'GET /staff/{id}/attendance-history',
            ok: false,
            message: 'No staff id',
            durationMs: 0,
          }),
  ];

  return Promise.all(probes.map((p) => p()));
}
