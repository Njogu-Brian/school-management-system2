import type { LessonPlanQueueFilters } from '@erp/core';
import { useEffect, useMemo, useState } from 'react';

export function useModerationRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters: LessonPlanQueueFilters = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      classroom_id: classroomId ?? undefined,
      per_page: 20,
    }),
    [debouncedSearch, classroomId],
  );

  return {
    searchInput,
    setSearchInput,
    classroomId,
    setClassroomId,
    filters,
  };
}
