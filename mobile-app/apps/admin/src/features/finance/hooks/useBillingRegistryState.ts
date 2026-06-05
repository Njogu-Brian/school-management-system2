import type { InvoiceListFilters } from '@erp/core';
import type { InvoiceStatusFilter } from '@erp/ui';
import { useEffect, useMemo, useState } from 'react';

const DEBOUNCE_MS = 350;

export function useBillingRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [status, setStatus] = useState<InvoiceStatusFilter>('all');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters = useMemo((): InvoiceListFilters => {
    const f: InvoiceListFilters = { per_page: 25 };
    if (debouncedSearch) f.search = debouncedSearch;
    if (status !== 'all') f.status = status;
    return f;
  }, [debouncedSearch, status]);

  return { searchInput, setSearchInput, status, setStatus, filters };
}
