import type { FinanceTransactionListFilters, FinanceTransactionViewFilter } from '@erp/core';
import { useEffect, useMemo, useState } from 'react';

const DEBOUNCE_MS = 350;

export function useCollectionsTransactionState(initialView: FinanceTransactionViewFilter = 'all') {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [view, setView] = useState<FinanceTransactionViewFilter>(initialView);

  useEffect(() => {
    setView(initialView);
  }, [initialView]);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters = useMemo((): FinanceTransactionListFilters => {
    const f: FinanceTransactionListFilters = { per_page: 25, view };
    if (debouncedSearch) f.search = debouncedSearch;
    return f;
  }, [debouncedSearch, view]);

  return { searchInput, setSearchInput, view, setView, filters };
}
