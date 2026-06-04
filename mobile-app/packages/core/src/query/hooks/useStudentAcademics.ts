import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { academicsApi } from '../../api/academics.api';
import {
  displayCategoryToApiTypes,
  normalizeAcademicSummary,
  normalizeAssessmentHistoryRow,
} from '../../students/academics';
import type {
  AssessmentDisplayCategory,
  AssessmentHistoryMeta,
} from '../../types/studentAcademics';
import { queryKeys } from '../queryKeys';

export interface StudentAssessmentHistoryFilters {
  category?: AssessmentDisplayCategory;
  subjectId?: number | null;
  termId?: number | null;
  academicYearId?: number | null;
}

type HistoryPage = {
  rows: ReturnType<typeof normalizeAssessmentHistoryRow>[];
  currentPage: number;
  lastPage: number;
  total: number;
  meta: AssessmentHistoryMeta | null;
};

export function useStudentAcademicSummary(
  studentId: number,
  options?: { enabled?: boolean; termId?: number; academicYearId?: number },
) {
  return useQuery({
    queryKey: queryKeys.students.academicSummary(studentId, {
      termId: options?.termId,
      academicYearId: options?.academicYearId,
    }),
    queryFn: async () => {
      const res = await academicsApi.getAcademicSummary(studentId, {
        term_id: options?.termId,
        academic_year_id: options?.academicYearId,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load academic summary.');
      }
      return normalizeAcademicSummary(res.data);
    },
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 60_000,
  });
}

export function useStudentReportCards(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.students.reportCards(studentId),
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

export function useStudentReportCardDetail(
  reportCardId: number | null | undefined,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.students.reportCardDetail(reportCardId ?? 0),
    queryFn: async () => {
      const res = await academicsApi.getReportCard(reportCardId as number);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load report card.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && (reportCardId ?? 0) > 0,
    staleTime: 120_000,
  });
}

export function useStudentAssessmentHistory(
  studentId: number,
  filters: StudentAssessmentHistoryFilters = {},
  options?: { enabled?: boolean },
) {
  const category = filters.category ?? 'all';
  const apiType = displayCategoryToApiTypes(category);

  return useInfiniteQuery({
    queryKey: queryKeys.students.assessmentHistory(studentId, {
      category,
      subjectId: filters.subjectId ?? null,
      termId: filters.termId ?? null,
      academicYearId: filters.academicYearId ?? null,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }): Promise<HistoryPage> => {
      const res = await academicsApi.getAssessmentHistory(studentId, {
        page: pageParam,
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
      const meta = res.meta ?? null;

      return {
        rows: page.data.map(normalizeAssessmentHistoryRow),
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        meta,
      };
    },
    getNextPageParam: (last) =>
      last.currentPage < last.lastPage ? last.currentPage + 1 : undefined,
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 45_000,
  });
}
