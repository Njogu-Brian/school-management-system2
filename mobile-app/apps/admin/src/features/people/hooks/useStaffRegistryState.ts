import type {
  StaffEmploymentStatusFilter,
  StaffGenderFilter,
  StaffListFilters,
} from '@erp/core';
import { useEffect, useMemo, useState } from 'react';

export function useStaffRegistryState() {
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [departmentId, setDepartmentId] = useState<number | null>(null);
  const [staffCategoryId, setStaffCategoryId] = useState<number | null>(null);
  const [role, setRole] = useState<string | null>(null);
  const [employmentStatus, setEmploymentStatus] =
    useState<StaffEmploymentStatusFilter>('all');
  const [gender, setGender] = useState<StaffGenderFilter>('all');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters: StaffListFilters = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      departmentId,
      staffCategoryId,
      role,
      employmentStatus,
      gender,
      perPage: 25,
    }),
    [debouncedSearch, departmentId, staffCategoryId, role, employmentStatus, gender],
  );

  return {
    searchInput,
    setSearchInput,
    departmentId,
    setDepartmentId,
    staffCategoryId,
    setStaffCategoryId,
    role,
    setRole,
    employmentStatus,
    setEmploymentStatus,
    gender,
    setGender,
    filters,
  };
}
