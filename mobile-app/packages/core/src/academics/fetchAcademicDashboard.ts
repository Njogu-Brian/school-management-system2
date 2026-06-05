import { academicsWorkspaceApi } from '../api/academicsWorkspace.api';
import { settingsApi } from '../api/settings.api';
import type { AcademicDashboardKpis, ExamListRecord } from '../types/academics';

function countByStatus(exams: ExamListRecord[]): Record<string, number> {
  const breakdown: Record<string, number> = {};
  for (const exam of exams) {
    const key = exam.status ?? 'unknown';
    breakdown[key] = (breakdown[key] ?? 0) + 1;
  }
  return breakdown;
}

/** Compose academics dashboard KPIs from existing endpoints (no `/academics/summary`). */
export async function fetchAcademicDashboard(): Promise<AcademicDashboardKpis> {
  const [examsRes, queueRes, yearsRes] = await Promise.all([
    academicsWorkspaceApi.listExams({ per_page: 100 }),
    academicsWorkspaceApi.listLessonPlanReviewQueue({ per_page: 1 }),
    settingsApi.getAcademicYears(),
  ]);

  const exams = examsRes.success && examsRes.data ? (examsRes.data.data ?? []) : [];
  const breakdown = countByStatus(exams);

  let trendSummary: AcademicDashboardKpis['trendSummary'] = [];
  let trendClassroomId: number | null = null;
  let trendTermId: number | null = null;
  let trendAcademicYearId: number | null = null;

  const years = yearsRes.success && yearsRes.data ? yearsRes.data : [];
  const activeYear = years.find((y) => y.is_active) ?? years[0];
  if (activeYear) {
    const termsRes = await settingsApi.getTerms(activeYear.id);
    const terms = termsRes.success && termsRes.data ? termsRes.data : [];
    const currentTerm = terms.find((t) => t.is_current) ?? terms[terms.length - 1];
    const classesRes = await settingsApi.getClasses();
    const classes = classesRes.success && classesRes.data ? classesRes.data : [];
    const firstClass = classes[0];

    if (currentTerm && firstClass) {
      trendClassroomId = firstClass.id;
      trendTermId = currentTerm.id;
      trendAcademicYearId = activeYear.id;
      const trendsRes = await academicsWorkspaceApi.getExamTrends({
        academic_year_id: activeYear.id,
        term_id: currentTerm.id,
        classroom_id: firstClass.id,
      });
      if (trendsRes.success && trendsRes.data) {
        trendSummary = Array.isArray(trendsRes.data) ? trendsRes.data.slice(0, 6) : [];
      }
    }
  }

  return {
    examsDraft: breakdown.draft ?? 0,
    examsMarking: breakdown.marking ?? 0,
    examsModeration: breakdown.moderation ?? 0,
    examsPublished: breakdown.published ?? 0,
    lessonPlansPendingReview: queueRes.success && queueRes.data ? (queueRes.data.total ?? 0) : 0,
    examStatusBreakdown: breakdown,
    trendSummary,
    trendClassroomId,
    trendTermId,
    trendAcademicYearId,
  };
}
