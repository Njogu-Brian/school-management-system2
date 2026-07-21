import { useInfiniteNotifications, useAcknowledgeNotification, useMarkNotificationRead } from '@erp/core';
import { AcademicScreenHeader, Button, EmptyState, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { navigateDashboardBack } from '../../../navigation/navigateWorkspace';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { showSuccess } from '../../shared/utils/feedback';
import { resolveNotificationDeepLink } from '../resolveDeepLink';

type Props = StackScreenProps<DashboardStackParamList, 'NotificationDetail'>;

export const NotificationDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { notificationId } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
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
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (listQuery.isError || !notification) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <EmptyState
          title="Notification not found"
          message={(listQuery.error as Error)?.message ?? 'This notification could not be loaded.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void listQuery.refetch()}
        />
        <View style={{ paddingHorizontal: spacing.lg }}>
          <Button label="Go back" variant="ghost" onPress={() => navigateDashboardBack(navigation)} />
        </View>
      </ScreenContainer>
    );
  }

  if (!notification.is_read) {
    void markRead.mutate(notification.id);
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Notification" onBack={() => navigateDashboardBack(navigation)} />
      <FinanceFieldSection
        title="Details"
        rows={[
          { label: 'Category', value: notification.category },
          { label: 'Module', value: notification.source_module ?? '—' },
          { label: 'Time', value: formatDateTimeLabel(notification.created_at) },
        ]}
      />
      <Text
        style={{
          fontWeight: typography.title.fontWeight,
          fontSize: typography.title.fontSize,
          color: palette.textPrimary,
          marginTop: spacing.md,
        }}
      >
        {notification.title}
      </Text>
      <Text
        style={{
          color: palette.textSecondary,
          marginTop: spacing.sm,
          fontSize: typography.body.fontSize,
          lineHeight: typography.body.lineHeight,
        }}
      >
        {notification.body}
      </Text>
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
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Handled</Text>
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
