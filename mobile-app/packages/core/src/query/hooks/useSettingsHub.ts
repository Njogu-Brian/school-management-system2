import { useQuery } from '@tanstack/react-query';
import { settingsApi } from '../../api/settings.api';
import { queryKeys } from '../queryKeys';

export function useSchoolSettings(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.school(),
    queryFn: async () => {
      const res = await settingsApi.getSchool();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load school settings.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useAcademicYearsSettings(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.academicYears(),
    queryFn: async () => {
      const res = await settingsApi.getAcademicYears();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load academic years.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useTermsSettings(
  academicYearId?: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.settings.terms(academicYearId),
    queryFn: async () => {
      const res = await settingsApi.getTerms(academicYearId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load terms.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useSettingsClasses(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.classes(),
    queryFn: async () => {
      const res = await settingsApi.getClasses();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load classes.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useSettingsStreams(classId: number | null, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.streams(classId ?? 0),
    queryFn: async () => {
      const res = await settingsApi.getStreams(classId as number);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load streams.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && (classId ?? 0) > 0,
    staleTime: 120_000,
  });
}

export function useSettingsSubjects(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.subjects(),
    queryFn: async () => {
      const res = await settingsApi.getSubjects();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load subjects.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useGradingSettings(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.grading(),
    queryFn: async () => {
      const res = await settingsApi.getGrading();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load grading settings.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useRolesSettings(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.settings.roles(),
    queryFn: async () => {
      const res = await settingsApi.getRoles();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load roles.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}
