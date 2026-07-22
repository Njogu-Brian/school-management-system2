import {
  useDeleteNotification,
  useInfiniteNotifications,
  useAcknowledgeNotification,
  useMarkAllNotificationsRead,
  useMarkNotificationRead,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { confirmAction, showSuccess } from '../../shared/utils/feedback';

export const NotificationsListScreen: React.FC = () => {
  const navigation = useNavigation();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const listQuery = useInfiniteNotifications();
  const markRead = useMarkNotificationRead();
  const markAll = useMarkAllNotificationsRead();
  const acknowledge = useAcknowledgeNotification();
  const remove = useDeleteNotification();

  const items = useMemo(() => listQuery.data?.pages.flatMap((p) => p.items) ?? [], [listQuery.data]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => item.id}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="Notifications"
              onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
            />
            <Pressable
              onPress={() => void markAll.mutateAsync().then(() => showSuccess('Done', 'All marked read.'))}
              style={{ marginBottom: spacing.sm }}
            >
              <Text
                style={{
                  color: colors.primary,
                  fontWeight: '600',
                  fontSize: typography.caption.fontSize,
                }}
              >
                Mark all read
              </Text>
            </Pressable>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => {
              if (!item.is_read) void markRead.mutateAsync(item.id);
            }}
            onLongPress={() =>
              confirmAction('Delete', 'Delete this notification?', 'Delete', () => void remove.mutateAsync(item.id), true)
            }
            style={[
              styles.row,
              {
                borderColor: palette.border,
                backgroundColor: palette.surface,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: item.is_read ? 0.85 : 1,
              },
            ]}
          >
            {!item.is_read ? <View style={[styles.unread, { backgroundColor: colors.primary }]} /> : null}
            <Text style={{ fontWeight: '700', color: palette.textPrimary }}>{item.title}</Text>
            <Text
              style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}
              numberOfLines={3}
            >
              {item.body}
            </Text>
            <Text
              style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}
            >
              {[item.category, item.created_at?.slice(0, 16).replace('T', ' ')].filter(Boolean).join(' · ')}
            </Text>
            {item.requires_action && !item.is_acknowledged ? (
              <Pressable
                onPress={() =>
                  void acknowledge.mutateAsync(item.id).then(() => showSuccess('Done', 'Alert marked as handled.'))
                }
                style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
              >
                <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
                  Mark done
                </Text>
              </Pressable>
            ) : item.is_acknowledged ? (
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                  marginTop: spacing.sm,
                }}
              >
                Handled
              </Text>
            ) : null}
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) void listQuery.fetchNextPage();
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={6} />
          ) : listQuery.isError ? (
            <EmptyState
              title="Could not load notifications"
              message={(listQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <EmptyState title="No notifications" message="You're all caught up." icon="notifications-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  unread: { width: 8, height: 8, borderRadius: 4, marginBottom: 6 },
});
