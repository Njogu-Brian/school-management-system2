import { useInfiniteNotifications, useAcknowledgeNotification, useMarkNotificationRead } from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { showSuccess } from '../../shared/utils/feedback';
import { resolveNotificationDeepLink } from '../resolveDeepLink';

type Props = StackScreenProps<DashboardStackParamList, 'NotificationDetail'>;

export const NotificationDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { notificationId } = route.params;
  const { palette, spacing } = useTheme();
  const listQuery = useInfiniteNotifications();
  const markRead = useMarkNotificationRead();
  const acknowledge = useAcknowledgeNotification();

  const notification = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items).find((n) => n.id === notificationId),
    [listQuery.data, notificationId],
  );

  if (listQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator />
      </ScreenContainer>
    );
  }

  if (!notification) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Notification" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>Notification not found.</Text>
      </ScreenContainer>
    );
  }

  if (!notification.is_read) {
    void markRead.mutate(notification.id);
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Notification" onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Details"
        rows={[
          { label: 'Category', value: notification.category },
          { label: 'Module', value: notification.source_module ?? '—' },
          { label: 'Time', value: formatDateTimeLabel(notification.created_at) },
        ]}
      />
      <Text style={{ fontWeight: '700', fontSize: 18, color: palette.textPrimary, marginTop: spacing.md }}>
        {notification.title}
      </Text>
      <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, lineHeight: 22 }}>{notification.body}</Text>
      {notification.requires_action && !notification.is_acknowledged ? (
        <Button
          label="Mark as done"
          onPress={() =>
            void acknowledge.mutateAsync(notification.id).then(() => showSuccess('Done', 'Alert marked as handled.'))
          }
          style={{ marginTop: spacing.lg }}
        />
      ) : notification.is_acknowledged ? (
        <View style={{ marginTop: spacing.lg }}>
          <Text style={{ color: palette.textSecondary }}>Handled</Text>
        </View>
      ) : null}
      <Button
        label="Open related screen"
        onPress={() => {
          resolveNotificationDeepLink(navigation, notification);
          navigation.goBack();
        }}
        style={{ marginTop: spacing.md }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
