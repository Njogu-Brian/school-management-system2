import {
  useDeleteNotification,
  useInfiniteNotifications,
  useAcknowledgeNotification,
  useMarkAllNotificationsRead,
  useMarkNotificationRead,
} from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
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
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showSuccess } from '../../shared/utils/feedback';
import { NOTIFICATION_CATEGORIES } from '../constants';

type Props = StackScreenProps<DashboardStackParamList, 'NotificationsList'>;

export const NotificationsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
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
            <AcademicScreenHeader title="Notifications" onBack={() => navigation.goBack()} />
            <View style={{ flexDirection: 'row', gap: 8, marginBottom: spacing.sm }}>
              <Pressable onPress={() => void markAll.mutateAsync().then(() => showSuccess('Done', 'All marked read.'))}>
                <Text style={{ color: colors.primary, fontWeight: '600', fontSize: fontSizes.xs }}>Mark all read</Text>
              </Pressable>
              <Pressable onPress={() => navigateToActivity(navigation)}>
                <Text style={{ color: colors.primary, fontWeight: '600', fontSize: fontSizes.xs }}>Activity center</Text>
              </Pressable>
            </View>
            <TextInput
              value={search}
              onChangeText={setSearch}
              placeholder="Search notifications"
              placeholderTextColor={palette.textSecondary}
              style={[styles.search, { borderColor: palette.border, color: palette.textPrimary }]}
            />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: spacing.sm }}>
              {NOTIFICATION_CATEGORIES.map((c) => (
                <Pressable
                  key={c.id}
                  onPress={() => setCategory(c.id)}
                  style={[styles.chip, category === c.id && { borderColor: colors.primary }]}
                >
                  <Text style={{ fontSize: fontSizes.xs }}>{c.label}</Text>
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
            style={[styles.row, { borderColor: palette.border, opacity: item.is_read ? 0.85 : 1 }]}
          >
            {!item.is_read ? <View style={[styles.unread, { backgroundColor: colors.primary }]} /> : null}
            <Text style={{ fontWeight: '700', color: palette.textPrimary }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }} numberOfLines={2}>
              {item.body}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
              {[item.category, formatDateTimeLabel(item.created_at)].filter(Boolean).join(' · ')}
            </Text>
            {item.requires_action && !item.is_acknowledged ? (
              <Pressable
                onPress={() =>
                  void acknowledge.mutateAsync(item.id).then(() => showSuccess('Done', 'Alert marked as handled.'))
                }
                style={{ marginTop: 8, alignSelf: 'flex-start' }}
              >
                <Text style={{ color: colors.primary, fontWeight: '700', fontSize: fontSizes.xs }}>
                  Mark done
                </Text>
              </Pressable>
            ) : item.is_acknowledged ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 8 }}>
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
            <ActivityIndicator color={colors.primary} />
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
  search: { borderWidth: 1, borderRadius: 8, padding: 10, marginBottom: 8 },
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 14, paddingHorizontal: 8, paddingVertical: 4 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
  unread: { width: 8, height: 8, borderRadius: 4, marginBottom: 6 },
});
