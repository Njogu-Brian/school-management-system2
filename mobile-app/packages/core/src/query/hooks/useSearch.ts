import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { searchApi, type SearchModuleFilter } from '../../api/search.api';
import { queryKeys } from '../queryKeys';

export function useSearchSuggestions(query: string, enabled = true) {
  return useQuery({
    queryKey: queryKeys.search.suggest(query),
    queryFn: async () => {
      const res = await searchApi.suggest(query);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Suggestions failed.');
      }
      return res.data;
    },
    enabled: enabled && query.trim().length >= 1,
    staleTime: 60_000,
  });
}

export function useInfiniteGlobalSearch(options: {
  query: string;
  module?: SearchModuleFilter;
  enabled?: boolean;
}) {
  const q = options.query.trim();

  return useInfiniteQuery({
    queryKey: queryKeys.search.global(q, options.module ?? 'all'),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await searchApi.search({
        query: q,
        module: options.module,
        page: pageParam as number,
        limit: 20,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Search failed.');
      }
      const page = res.data;
      return {
        items: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last) => (last.hasMore ? last.currentPage + 1 : undefined),
    enabled: (options.enabled !== false) && q.length >= 2,
    staleTime: 120_000,
  });
}
