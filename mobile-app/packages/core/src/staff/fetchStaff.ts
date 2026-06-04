import { staffApi } from '../api/staff.api';
import type { StaffFilterOptions, StaffListFilters, StaffSummary } from '../types/staff';
import { buildStaffQueryParams, toStaffSummary } from './normalize';

export async function fetchStaffFilterOptions(): Promise<StaffFilterOptions> {
  const res = await staffApi.filterOptions();
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load staff filters.');
  }
  return res.data;
}

export async function fetchStaffListPage(
  filters: StaffListFilters,
  page: number,
): Promise<{ items: StaffSummary[]; hasMore: boolean; total: number }> {
  const res = await staffApi.list(buildStaffQueryParams(filters, page));
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load staff.');
  }

  const { data: rows, current_page, last_page, total } = res.data;
  const items = rows.map((raw) => toStaffSummary(raw));

  return {
    items,
    hasMore: current_page < last_page,
    total,
  };
}
