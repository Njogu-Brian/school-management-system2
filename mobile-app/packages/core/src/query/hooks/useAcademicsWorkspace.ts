import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { academicsWorkspaceApi } from '../../api/academicsWorkspace.api';
import { academicsApi } from '../../api/academics.api';
import { examReportsApi } from '../../api/examReports.api';
import { fetchAcademicDashboard } from '../../academics/fetchAcademicDashboard';
import {
  normalizeExamSummary,
  normalizeLessonPlanSummary,
  normalizeMarkRow,
} from '../../academics/normalize';
import {
  displayCategoryToApiTypes,
  normalizeAssessmentHistoryRow,
} from '../../students/academics';
import type {
  ExamListFilters,
  LessonPlanQueueFilters,
  MarksListFilters,
  MarksMatrixFilters,
} from '../../types/academics';
import type { AssessmentDisplayCategory } from '../../types/studentAcademics';
import { queryKeys } from '../queryKeys';

export function useAcademicDashboard(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.dashboard(),
    queryFn: fetchAcademicDashboard,
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useInfiniteExams(filters: ExamListFilters, options?: { enabled?: boolean }) {
  return useInfiniteQuery({
    queryKey: queryKeys.academics.exams(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await academicsWorkspaceApi.listExams({
        ...filters,
        page: pageParam as number,
        per_page: filters.per_page ?? 25,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load exams.');
      }
      const page = res.data;
      let items = page.data.map(normalizeExamSummary);
      if (filters.search?.trim()) {
        const q = filters.search.trim().toLowerCase();
        items = items.filter(
          (e) =>
            e.name.toLowerCase().includes(q) ||
            (e.classroomName?.toLowerCase().includes(q) ?? false) ||
            (e.subjectName?.toLowerCase().includes(q) ?? false),
        );
      }
      if (filters.academic_year_id != null) {
        items = items.filter((e) => e.academicYearId === filters.academic_year_id);
      }
      if (filters.term_id != null) {
        items = items.filter((e) => e.termId === filters.term_id);
      }
      if (filters.classroom_id != null) {
        items = items.filter((e) => e.classroomId === filters.classroom_id);
      }
      return {
        items,
        raw: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useExamDetail(examId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.examDetail(examId),
    queryFn: async () => {
      const res = await academicsWorkspaceApi.getExam(examId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load exam.');
      }
      return normalizeExamSummary(res.data);
    },
    enabled: options?.enabled !== false && examId > 0,
    staleTime: 60_000,
  });
}

export function useExamMarkingOptions(examId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.examMarkingOptions(examId),
    queryFn: async () => {
      const res = await academicsWorkspaceApi.getExamMarkingOptions(examId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load marking options.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && examId > 0,
    staleTime: 60_000,
  });
}

export function useMarks(filters: MarksListFilters | null, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.marks(filters ?? {}),
    queryFn: async () => {
      if (!filters) throw new Error('Marks filters required.');
      const res = await academicsWorkspaceApi.listMarks(filters);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load marks.');
      }
      return (res.data.data ?? []).map(normalizeMarkRow);
    },
    enabled:
      options?.enabled !== false &&
      filters != null &&
      filters.exam_id > 0 &&
      filters.subject_id > 0 &&
      filters.classroom_id > 0,
    staleTime: 45_000,
  });
}

export function useMarksMatrixContext(classroomId?: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.marksMatrixContext(classroomId),
    queryFn: async () => {
      const res = await academicsWorkspaceApi.getMarksMatrixContext(classroomId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load matrix context.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useMarksMatrix(filters: MarksMatrixFilters | null, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.marksMatrix(filters ?? {}),
    queryFn: async () => {
      if (!filters) throw new Error('Matrix filters required.');
      const res = await academicsWorkspaceApi.getMarksMatrix(filters);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load marks matrix.');
      }
      return res.data;
    },
    enabled:
      options?.enabled !== false &&
      filters != null &&
      filters.exam_type_id > 0 &&
      filters.classroom_id > 0,
    staleTime: 45_000,
  });
}

export interface WorkspaceAssessmentHistoryFilters {
  category?: AssessmentDisplayCategory;
  subjectId?: number | null;
  termId?: number | null;
  academicYearId?: number | null;
}

export function useAssessmentHistory(
  studentId: number,
  filters: WorkspaceAssessmentHistoryFilters = {},
  options?: { enabled?: boolean },
) {
  const category = filters.category ?? 'all';
  const apiType = displayCategoryToApiTypes(category);

  return useInfiniteQuery({
    queryKey: queryKeys.academics.assessmentHistory(studentId, {
      category,
      subjectId: filters.subjectId ?? null,
      termId: filters.termId ?? null,
      academicYearId: filters.academicYearId ?? null,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await academicsApi.getAssessmentHistory(studentId, {
        page: pageParam as number,
        per_page: 25,
        type: apiType,
        subject_id: filters.subjectId ?? undefined,
        term_id: filters.termId ?? undefined,
        academic_year_id: filters.academicYearId ?? undefined,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load assessment history.');
      }
      const page = res.data;
      return {
        rows: page.data.map(normalizeAssessmentHistoryRow),
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        meta: res.meta ?? null,
      };
    },
    getNextPageParam: (last) =>
      last.currentPage < last.lastPage ? last.currentPage + 1 : undefined,
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 45_000,
  });
}

export function useReportCards(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.reportCards(studentId),
    queryFn: async () => {
      const res = await academicsApi.getReportCards(studentId, { per_page: 50 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load report cards.');
      }
      return res.data.data;
    },
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 60_000,
  });
}

export function useReportCardDetail(reportCardId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.reportCardDetail(reportCardId),
    queryFn: async () => {
      const res = await academicsApi.getReportCard(reportCardId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load report card.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && reportCardId > 0,
    staleTime: 60_000,
  });
}

export function useModerationQueue(
  filters: LessonPlanQueueFilters,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.academics.moderationQueue(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await academicsWorkspaceApi.listLessonPlanReviewQueue({
        ...filters,
        page: pageParam as number,
        per_page: filters.per_page ?? 20,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load moderation queue.');
      }
      const page = res.data;
      let items = page.data.map(normalizeLessonPlanSummary);
      if (filters.search?.trim()) {
        const q = filters.search.trim().toLowerCase();
        items = items.filter(
          (lp) =>
            lp.topic.toLowerCase().includes(q) ||
            (lp.teacherName?.toLowerCase().includes(q) ?? false) ||
            (lp.className?.toLowerCase().includes(q) ?? false),
        );
      }
      return {
        items,
        raw: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useLessonPlanDetail(lessonPlanId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.academics.lessonPlanDetail(lessonPlanId),
    queryFn: async () => {
      const res = await academicsWorkspaceApi.getLessonPlan(lessonPlanId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load lesson plan.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && lessonPlanId > 0,
    staleTime: 60_000,
  });
}

export function useLessonPlanModerationActions() {
  const queryClient = useQueryClient();

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.academics.all });
  };

  const approve = useMutation({
    mutationFn: async ({ id, notes }: { id: number; notes?: string }) => {
      const res = await academicsWorkspaceApi.approveLessonPlan(id, notes);
      if (!res.success) {
        throw new Error(res.message || 'Failed to approve lesson plan.');
      }
      return res.data;
    },
    onSuccess: invalidate,
  });

  const reject = useMutation({
    mutationFn: async ({ id, notes }: { id: number; notes: string }) => {
      const res = await academicsWorkspaceApi.rejectLessonPlan(id, notes);
      if (!res.success) {
        throw new Error(res.message || 'Failed to reject lesson plan.');
      }
      return res.data;
    },
    onSuccess: invalidate,
  });

  return { approve, reject };
}

export function useEnterMarks() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (data: Parameters<typeof academicsWorkspaceApi.enterMarks>[0]) => {
      const res = await academicsWorkspaceApi.enterMarks(data);
      if (!res.success) throw new Error(res.message || 'Failed to save marks.');
      return res;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.academics.all });
    },
  });
}

export function useEnterMarksMatrix() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (data: Parameters<typeof academicsWorkspaceApi.enterMarksMatrix>[0]) => {
      const res = await academicsWorkspaceApi.enterMarksMatrix(data);
      if (!res.success) throw new Error(res.message || 'Failed to save marks matrix.');
      return res;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.academics.all });
    },
  });
}

export function useExamClassSheet(
  params: {
    examId?: number;
    examSessionId?: number;
    classroomId: number;
    streamId?: number;
  } | null,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.academics.examClassSheet(params ?? {}),
    queryFn: async () => {
      if (!params) throw new Error('Class sheet params required.');
      const mode = params.examSessionId ? 'exam_session' : 'exam';
      const res = await examReportsApi.getClassSheet({
        mode,
        exam_id: params.examId,
        exam_session_id: params.examSessionId,
        classroom_id: params.classroomId,
        stream_id: params.streamId,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load exam class sheet.');
      }
      return res.data;
    },
    enabled:
      options?.enabled !== false &&
      params != null &&
      params.classroomId > 0 &&
      (params.examSessionId != null || (params.examId ?? 0) > 0),
    staleTime: 60_000,
  });
}

export function useExamSessions(
  filters: {
    academic_year_id?: number;
    term_id?: number;
    classroom_id?: number;
    stream_id?: number;
  },
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: [...queryKeys.academics.all, 'exam-sessions', filters],
    queryFn: async () => {
      const res = await academicsWorkspaceApi.listExamSessions(filters);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load exam sessions.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

/** Aliases matching sprint hook names */
export { useInfiniteExams as useExams };
