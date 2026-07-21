import {
  useDeleteNotification,
  useInfiniteNotifications,
  useAcknowledgeNotification,
  useMarkAllNotificationsRead,
  useMarkNotificationRead,
} from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, SkeletonListRows, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { navigateDashboardBack } from '../../../navigation/navigateWorkspace';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showSuccess } from '../../shared/utils/feedback';
import { NOTIFICATION_CATEGORIES } from '../constants';

type Props = StackScreenProps<DashboardStackParamList, 'NotificationsList'>;

export const NotificationsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const [category, setCategory] = useState<string>('all');
  const [search, setSearch] = useState('');
  const listQuery = useInfiniteNotifications({
    category: category === 'all' ? undefined : category,
    search: search.trim() || undefined,
  });
  const markRead = useMarkNotificationRead();
  const markAll = useMarkAllNotificationsRead();
  const acknowledge = useAcknowledgeNotification();
  const remove = useDeleteNotification();

  const items = useMemo(() => listQuery.data?.pages.flatMap((p) => p.items) ?? [], [listQuery.data]);

  const openItem = (id: string, isRead: boolean) => {
    if (!isRead) void markRead.mutateAsync(id);
    navigation.navigate('NotificationDetail', { notificationId: id });
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => item.id}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="Notifications" onBack={() => navigateDashboardBack(navigation)} />
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.sm }}>
              <Pressable onPress={() => void markAll.mutateAsync().then(() => showSuccess('Done', 'All marked read.'))}>
                <Text
                  style={{
                    color: colors.primary,
                    fontWeight: typography.label.fontWeight,
                    fontSize: typography.caption.fontSize,
                  }}
                >
                  Mark all read
                </Text>
              </Pressable>
              <Pressable onPress={() => navigateToActivity(navigation)}>
                <Text
                  style={{
                    color: colors.primary,
                    fontWeight: typography.label.fontWeight,
                    fontSize: typography.caption.fontSize,
                  }}
                >
                  Activity center
                </Text>
              </Pressable>
            </View>
            <TextInput
              value={search}
              onChangeText={setSearch}
              placeholder="Search notifications"
              placeholderTextColor={palette.textSecondary}
              style={[
                styles.search,
                {
                  borderColor: palette.borderSubtle,
                  color: palette.textPrimary,
                  borderRadius: radius.control,
                  padding: spacing.mdSm,
                  marginBottom: spacing.sm,
                  fontSize: typography.body.fontSize,
                  backgroundColor: palette.surfaceRaised,
                },
              ]}
            />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs + 2, marginBottom: spacing.sm }}>
              {NOTIFICATION_CATEGORIES.map((c) => (
                <Pressable
                  key={c.id}
                  onPress={() => setCategory(c.id)}
                  style={[
                    styles.chip,
                    {
                      borderColor: category === c.id ? colors.primary : palette.borderSubtle,
                      borderRadius: radius.chip,
                      paddingHorizontal: spacing.sm,
                      paddingVertical: spacing.xs,
                      backgroundColor: category === c.id ? `${colors.primary}14` : palette.surfaceRaised,
                    },
                  ]}
                >
                  <Text
                    style={{
                      fontSize: typography.caption.fontSize,
                      color: category === c.id ? colors.primary : palette.textSecondary,
                      fontWeight: category === c.id ? '600' : '500',
                    }}
                  >
                    {c.label}
                  </Text>
                </Pressable>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => openItem(item.id, item.is_read)}
            onLongPress={() =>
              confirmAction('Delete', 'Delete this notification?', 'Delete', () => void remove.mutateAsync(item.id), true)
            }
            style={[
              elevation[1],
              styles.row,
              {
                borderColor: palette.borderSubtle,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: item.is_read ? 0.85 : 1,
              },
            ]}
          >
            {!item.is_read ? (
              <View style={[styles.unread, { backgroundColor: colors.primary }]} />
            ) : null}
            <Text
              style={{
                fontWeight: typography.titleSmall.fontWeight,
                color: palette.textPrimary,
                fontSize: typography.titleSmall.fontSize,
              }}
            >
              {item.title}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs,
              }}
              numberOfLines={2}
            >
              {item.body}
            </Text>
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs,
              }}
            >
              {[item.category, formatDateTimeLabel(item.created_at)].filter(Boolean).join(' · ')}
            </Text>
            {item.requires_action && !item.is_acknowledged ? (
              <Pressable
                onPress={() =>
                  void acknowledge.mutateAsync(item.id).then(() => showSuccess('Done', 'Alert marked as handled.'))
                }
                style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
              >
                <Text
                  style={{
                    color: colors.primary,
                    fontWeight: typography.label.fontWeight,
                    fontSize: typography.caption.fontSize,
                  }}
                >
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
        ListFooterComponent={listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
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

function navigateToActivity(navigation: Props['navigation']) {
  navigation.navigate('ActivityCenter');
}

const styles = StyleSheet.create({
  search: { borderWidth: 1 },
  chip: { borderWidth: 1 },
  row: { borderWidth: StyleSheet.hairlineWidth },
  unread: { width: 8, height: 8, borderRadius: 4, marginBottom: 6 },
});
