import type { ExamListFilters } from '@erp/core';
import { useEffect, useMemo, useState } from 'react';

export function useExamsRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [status, setStatus] = useState('');
  const [termId, setTermId] = useState<number | null>(null);
  const [academicYearId, setAcademicYearId] = useState<number | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters: ExamListFilters = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      status: status || undefined,
      term_id: termId ?? undefined,
      academic_year_id: academicYearId ?? undefined,
      per_page: 25,
    }),
    [debouncedSearch, status, termId, academicYearId],
  );

  return {
    searchInput,
    setSearchInput,
    status,
    setStatus,
    termId,
    setTermId,
    academicYearId,
    setAcademicYearId,
    filters,
  };
}
