import type {
  StudentEnrollmentStatusFilter,
  StudentGenderFilter,
  StudentListFilters,
} from '@erp/core';
import { useEffect, useMemo, useState } from 'react';

export function useStudentRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [gradeLevel, setGradeLevel] = useState<number | string | null>(null);
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);
  const [status, setStatus] = useState<StudentEnrollmentStatusFilter>('all');
  const [gender, setGender] = useState<StudentGenderFilter>('all');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters: StudentListFilters = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      gradeLevel,
      classroomId,
      streamId,
      status,
      gender,
      perPage: 25,
    }),
    [debouncedSearch, gradeLevel, classroomId, streamId, status, gender],
  );

  return {
    searchInput,
    setSearchInput,
    gradeLevel,
    setGradeLevel,
    classroomId,
    setClassroomId,
    streamId,
    setStreamId,
    status,
    setStatus,
    gender,
    setGender,
    filters,
  };
}
