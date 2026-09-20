import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationsApi } from '../../api/notifications.api';
import { queryKeys } from '../queryKeys';

/**
 * Unread badge count.
 *
 * No timer. This used to poll every 60 seconds from every screen carrying the
 * header chrome — 1,440 requests per device per day purely to redraw a number.
 * Push is already registered (see usePushNotifications), and both the foreground
 * listener and every read/acknowledge mutation invalidate
 * `queryKeys.notifications.all`, so the badge still updates the moment anything
 * changes. `refetchOnWindowFocus` covers notifications that arrived while the app
 * was backgrounded.
 */
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
  });
}

/**
 * Paged notification list.
 *
 * `refetchIntervalMs` is opt-in and off by default. The default used to be a
 * 20-second poll, and because the push providers mount this hook globally it ran
 * on every screen for every signed-in user — roughly 4,320 requests per device
 * per day, duplicating push notifications that were already delivering the same
 * events. Screens that genuinely want a live list can pass an interval.
 */
export function useInfiniteNotifications(options?: {
  enabled?: boolean;
  isRead?: boolean;
  category?: string;
  search?: string;
  refetchIntervalMs?: number;
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
    refetchInterval:
      options?.enabled === false || !options?.refetchIntervalMs ? false : options.refetchIntervalMs,
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

export function useAcknowledgeNotification() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => notificationsApi.acknowledge(id),
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
