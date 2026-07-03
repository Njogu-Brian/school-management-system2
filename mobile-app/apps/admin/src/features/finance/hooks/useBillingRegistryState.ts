import type { InvoiceListFilters } from '@erp/core';
import type { InvoiceStatusFilter } from '@erp/ui';
import { useEffect, useMemo, useState } from 'react';

const DEBOUNCE_MS = 350;

export function useBillingRegistryState(initialHasBalance = false) {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [status, setStatus] = useState<InvoiceStatusFilter>('all');
  const [hasBalance, setHasBalance] = useState(initialHasBalance);

  useEffect(() => {
    setHasBalance(initialHasBalance);
  }, [initialHasBalance]);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters = useMemo((): InvoiceListFilters => {
    const f: InvoiceListFilters = { per_page: 25 };
    if (debouncedSearch) f.search = debouncedSearch;
    if (status !== 'all') f.status = status;
    if (hasBalance) f.has_balance = true;
    return f;
  }, [debouncedSearch, status, hasBalance]);

  return { searchInput, setSearchInput, status, setStatus, hasBalance, setHasBalance, filters };
}
