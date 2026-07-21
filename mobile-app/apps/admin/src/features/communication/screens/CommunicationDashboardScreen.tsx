import { useAnnouncements, useCan, useCommunicationLogs } from '@erp/core';
import {
  DashboardHero,
  DashboardSection,
  EmptyState,
  KpiCard,
  QuickAction,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useMemo } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'CommunicationDashboard'>;

export const CommunicationDashboardScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const announcementsQuery = useAnnouncements({ enabled: canView, perPage: 50 });
  const logsQuery = useCommunicationLogs({ enabled: canView, channel: 'sms', perPage: 100 });

  const openAnnouncements = useCallback(() => navigation.navigate('AnnouncementsList'), [navigation]);

  const stats = useMemo(() => {
    const items = announcementsQuery.data?.data ?? [];
    const logs = logsQuery.data?.data ?? [];
    const now = new Date();
    const active = items.filter((a) => !a.expires_at || new Date(a.expires_at) >= now).length;
    const expired = items.filter((a) => a.expires_at && new Date(a.expires_at) < now).length;
    const delivered = logs.filter((l) => l.status === 'delivered' || l.status === 'sent').length;
    const failedSms = logs.filter((l) => l.status === 'failed').length;
    return {
      total: announcementsQuery.data?.total ?? items.length,
      active,
      expired,
      delivered,
      failedSms,
    };
  }, [announcementsQuery.data, logsQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need communication.view permission to open this workspace."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  const recent = announcementsQuery.data?.data?.slice(0, 3) ?? [];
  const kpiState = announcementsQuery.isLoading
    ? 'loading'
    : announcementsQuery.isError
      ? 'error'
      : 'success';
  const smsState = logsQuery.isLoading ? 'loading' : logsQuery.isError ? 'error' : 'success';
  const dashboardError = announcementsQuery.isError || logsQuery.isError;
  const errorMessage =
    (announcementsQuery.error as Error | null)?.message ??
    (logsQuery.error as Error | null)?.message ??
    'Something went wrong.';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={announcementsQuery.isRefetching || logsQuery.isRefetching}
            onRefresh={() => {
              void announcementsQuery.refetch();
              void logsQuery.refetch();
            }}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <DashboardHero
          variant="communication"
          title="Communication"
          subtitle="Announcements, SMS & parent engagement"
          meta={
            stats.active > 0
              ? `${stats.active} active announcement${stats.active === 1 ? '' : 's'}`
              : undefined
          }
        />

        {dashboardError ? (
          <EmptyState
            title="Could not load dashboard"
            message={errorMessage}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => {
              void announcementsQuery.refetch();
              void logsQuery.refetch();
            }}
          />
        ) : null}

        <WidgetGrid>
          <WidgetShell state={kpiState} title="Announcements" onRetry={() => void announcementsQuery.refetch()}>
            <KpiCard label="Announcements" value={String(stats.total)} icon="megaphone-outline" delta={`${stats.active} active`} deltaPositive />
          </WidgetShell>
          <WidgetShell state={kpiState} title="Expired">
            <KpiCard label="Expired" value={String(stats.expired)} icon="time-outline" />
          </WidgetShell>
          <WidgetShell state={smsState} title="SMS delivered" onRetry={() => void logsQuery.refetch()}>
            <KpiCard label="SMS delivered" value={String(stats.delivered)} icon="checkmark-done-outline" deltaPositive />
          </WidgetShell>
          <WidgetShell state={smsState} title="SMS failed">
            <KpiCard
              label="SMS failed"
              value={String(stats.failedSms)}
              icon="alert-circle-outline"
              delta={stats.failedSms > 0 ? 'Needs attention' : 'All clear'}
              deltaPositive={stats.failedSms === 0}
            />
          </WidgetShell>
        </WidgetGrid>

        <DashboardSection title="Quick actions">
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            <QuickAction label="Send SMS" icon="chatbubble-outline" onPress={() => navigation.navigate('SmsCompose')} />
            <QuickAction label="New announcement" icon="add-circle-outline" onPress={() => navigation.navigate('AnnouncementForm')} />
            <QuickAction label="Announcements" icon="megaphone-outline" onPress={openAnnouncements} />
            <QuickAction label="SMS history" icon="list-outline" onPress={() => navigation.navigate('SmsHistory')} />
            <QuickAction label="Templates" icon="document-text-outline" onPress={() => navigation.navigate('TemplatesList')} />
          </View>
        </DashboardSection>

        <DashboardSection title="Recent announcements">
          {recent.length === 0 ? (
            <EmptyState
              title="No announcements"
              message="No active announcements yet."
              icon="megaphone-outline"
            />
          ) : (
            recent.map((a) => (
              <Pressable
                key={a.id}
                onPress={() => navigation.navigate('AnnouncementDetail', { announcementId: a.id })}
                style={({ pressed }) => [
                  elevation[1],
                  {
                    marginBottom: spacing.sm,
                    padding: spacing.md,
                    borderWidth: StyleSheet.hairlineWidth,
                    borderColor: palette.borderSubtle,
                    backgroundColor: palette.surfaceRaised,
                    borderRadius: radius.card,
                    opacity: pressed ? 0.9 : 1,
                  },
                ]}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
                  {a.title}
                </Text>
                <Text
                  style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}
                  numberOfLines={2}
                >
                  {a.content}
                </Text>
              </Pressable>
            ))
          )}
        </DashboardSection>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
