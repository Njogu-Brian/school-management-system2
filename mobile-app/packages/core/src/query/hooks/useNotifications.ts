import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationsApi } from '../../api/notifications.api';
import { queryKeys } from '../queryKeys';

export function useUnreadNotificationCount(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.notifications.unreadCount(),
    queryFn: async () => {
      const res = await notificationsApi.unreadCount();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load unread count.');
      }
      return res.data.count;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
    refetchInterval: 60_000,
  });
}

export function useInfiniteNotifications(options?: {
  enabled?: boolean;
  isRead?: boolean;
  category?: string;
  search?: string;
}) {
  return useInfiniteQuery({
    queryKey: queryKeys.notifications.list({
      isRead: options?.isRead,
      category: options?.category,
      search: options?.search,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await notificationsApi.list({
        page: pageParam as number,
        per_page: 25,
        is_read: options?.isRead,
        category: options?.category,
        search: options?.search,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load notifications.');
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
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useMarkNotificationRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => notificationsApi.markRead(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.notifications.all });
    },
  });
}

export function useMarkAllNotificationsRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => notificationsApi.markAllRead(),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.notifications.all });
    },
  });
}

export function useDeleteNotification() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => notificationsApi.delete(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.notifications.all });
    },
  });
}
