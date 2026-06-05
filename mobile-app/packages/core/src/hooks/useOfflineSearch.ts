import { useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import type { SearchHit } from '../api/search.api';
/** Filter persisted TanStack cache when offline (students/staff list caches). */
export function useOfflineSearch(query: string): SearchHit[] {
  const qc = useQueryClient();
  const q = query.trim().toLowerCase();

  return useMemo(() => {
    if (q.length < 2) {
      return [];
    }
    const hits: SearchHit[] = [];
    const cache = qc.getQueryCache().getAll();

    for (const entry of cache) {
      const key = entry.queryKey;
      if (!Array.isArray(key)) {
        continue;
      }
      const data = entry.state.data as { pages?: { items: SearchHit[] }[]; data?: unknown[] } | undefined;
      if (key[0] === 'search' && key[1] === 'global' && data?.pages) {
        data.pages.forEach((page) => {
          page.items.forEach((item) => {
            if (item.title.toLowerCase().includes(q)) {
              hits.push(item);
            }
          });
        });
      }
      if (key[0] === 'students' && key[1] === 'list' && Array.isArray((data as { data?: unknown[] })?.data)) {
        ((data as { data: Array<{ id: number; full_name?: string; admission_number?: string }> }).data).forEach(
          (s) => {
            const title = s.full_name ?? `Student #${s.id}`;
            if (title.toLowerCase().includes(q) || (s.admission_number ?? '').toLowerCase().includes(q)) {
              hits.push({
                id: `student-${s.id}`,
                module: 'Students',
                title,
                subtitle: s.admission_number,
                route: `students/${s.id}`,
                metadata: { entity_type: 'student', entity_id: s.id },
              });
            }
          },
        );
      }
      if (key[0] === 'staff' && key[1] === 'list' && Array.isArray((data as { data?: unknown[] })?.data)) {
        ((data as { data: Array<{ id: number; full_name?: string; staff_id?: string }> }).data).forEach((s) => {
          const title = s.full_name ?? `Staff #${s.id}`;
          if (title.toLowerCase().includes(q) || (s.staff_id ?? '').toLowerCase().includes(q)) {
            hits.push({
              id: `staff-${s.id}`,
              module: 'Staff',
              title,
              subtitle: s.staff_id,
              route: `people/${s.id}`,
              metadata: { entity_type: 'staff', entity_id: s.id },
            });
          }
        });
      }
    }

    return hits.slice(0, 30);
  }, [qc, q]);
}
