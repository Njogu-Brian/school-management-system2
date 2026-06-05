import type { ApplicationListFilters, ApplicationStatus } from '@erp/core';
import { useEffect, useMemo, useState } from 'react';
import type { ApplicationStatusFilter } from '@erp/ui';

export function useApplicationRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [status, setStatus] = useState<ApplicationStatusFilter>('all');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters: ApplicationListFilters = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      status: status === 'all' ? undefined : (status as ApplicationStatus),
      per_page: 25,
    }),
    [debouncedSearch, status],
  );

  return {
    searchInput,
    setSearchInput,
    status,
    setStatus,
    filters,
  };
}
