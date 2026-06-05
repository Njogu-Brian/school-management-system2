import type { FinanceTransactionListFilters } from '@erp/core';
import type { ReconciliationQueueFilter } from '@erp/ui';
import { useEffect, useMemo, useState } from 'react';

const DEBOUNCE_MS = 350;

export function useReconciliationRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [queue, setQueue] = useState<ReconciliationQueueFilter>('pending');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters = useMemo((): FinanceTransactionListFilters => {
    const f: FinanceTransactionListFilters = { per_page: 25, queue };
    if (debouncedSearch) f.search = debouncedSearch;
    return f;
  }, [debouncedSearch, queue]);

  return { searchInput, setSearchInput, queue, setQueue, filters };
}
