import type { PaymentListFilters } from '@erp/core';
import { useEffect, useMemo, useState } from 'react';

const DEBOUNCE_MS = 350;

export function useCollectionsRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters = useMemo((): PaymentListFilters => {
    const f: PaymentListFilters = { per_page: 25, active_only: true };
    if (debouncedSearch) f.search = debouncedSearch;
    return f;
  }, [debouncedSearch]);

  return { searchInput, setSearchInput, filters };
}
