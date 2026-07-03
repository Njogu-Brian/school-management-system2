import { ADMIN_NAV_AREAS, type AdminAreaKey } from '@erp/core';
import type { SearchHit } from '@erp/core';

const AREA_ROUTE: Record<AdminAreaKey, string> = {
  dashboard: 'dashboard/home',
  approvals: 'approvals/home',
  admissions: 'admissions/workspace',
  students: 'students/registry',
  academics: 'academics/dashboard',
  finance: 'finance/dashboard',
  people: 'people/staff',
  operations: 'operations/dashboard',
  communication: 'communication/dashboard',
  reports: 'reports/home',
  settings: 'settings/home',
};

/** Client-side menu search hits for global search. */
export function searchMenuItems(query: string): SearchHit[] {
  const q = query.trim().toLowerCase();
  if (q.length < 2) return [];

  const hits: SearchHit[] = [];
  for (const area of ADMIN_NAV_AREAS) {
    const haystack = [area.label, area.description, ...area.sections].join(' ').toLowerCase();
    if (!haystack.includes(q) && !area.label.toLowerCase().includes(q)) {
      continue;
    }
    hits.push({
      id: `menu-${area.key}`,
      module: 'Menu',
      title: area.label,
      subtitle: area.sections.slice(0, 3).join(' · '),
      route: AREA_ROUTE[area.key],
      metadata: { entity_type: 'menu', entity_id: area.key },
    });
    for (const section of area.sections) {
      if (!section.toLowerCase().includes(q)) continue;
      hits.push({
        id: `menu-${area.key}-${section}`,
        module: 'Menu',
        title: `${area.label} — ${section}`,
        subtitle: area.description,
        route: AREA_ROUTE[area.key],
        metadata: { entity_type: 'menu', entity_id: area.key },
      });
    }
  }
  return hits;
}
