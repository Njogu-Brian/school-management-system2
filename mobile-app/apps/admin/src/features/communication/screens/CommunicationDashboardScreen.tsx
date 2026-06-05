import { useAnnouncements, useCan, useCommunicationLogs } from '@erp/core';
import { KpiCard, QuickAction, ScreenContainer, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useMemo } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'CommunicationDashboard'>;

export const CommunicationDashboardScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const announcementsQuery = useAnnouncements({ enabled: canView, perPage: 50 });
  const logsQuery = useCommunicationLogs({ enabled: canView, channel: 'sms', perPage: 100 });

  const openAnnouncements = useCallback(() => navigation.navigate('AnnouncementsList'), [navigation]);

  const stats = useMemo(() => {
    const items = announcementsQuery.data?.data ?? [];
    const logs = logsQuery.data?.data ?? [];
    const now = new Date();
    const active = items.filter(
      (a) => !a.expires_at || new Date(a.expires_at) >= now,
    ).length;
    const expired = items.filter(
      (a) => a.expires_at && new Date(a.expires_at) < now,
    ).length;
    const failedSms = logs.filter((l) => l.status === 'failed').length;
    return {
      total: announcementsQuery.data?.total ?? items.length,
      active,
      expired,
      failedSms,
    };
  }, [announcementsQuery.data, logsQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const recent = announcementsQuery.data?.data?.slice(0, 3) ?? [];
  const kpiState = announcementsQuery.isLoading ? 'loading' : announcementsQuery.isError ? 'error' : 'success';

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
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Communication
        </Text>

        <WidgetGrid>
          <WidgetShell state={kpiState} title="Announcements" onRetry={() => void announcementsQuery.refetch()}>
            <KpiCard label="Total" value={String(stats.total)} icon="megaphone-outline" />
          </WidgetShell>
          <WidgetShell state={kpiState} title="Active">
            <KpiCard label="Active" value={String(stats.active)} icon="radio-outline" />
          </WidgetShell>
          <WidgetShell state={kpiState} title="Expired">
            <KpiCard label="Expired" value={String(stats.expired)} icon="time-outline" />
          </WidgetShell>
          <WidgetShell state={logsQuery.isLoading ? 'loading' : 'success'} title="SMS failed">
            <KpiCard label="Failed SMS" value={String(stats.failedSms)} icon="alert-circle-outline" />
          </WidgetShell>
        </WidgetGrid>

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Workspaces
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <QuickAction label="Announcements" icon="megaphone-outline" onPress={openAnnouncements} />
          <QuickAction label="New announcement" icon="add-circle-outline" onPress={() => navigation.navigate('AnnouncementForm')} />
          <QuickAction label="Send SMS" icon="chatbubble-outline" onPress={() => navigation.navigate('SmsCompose')} />
          <QuickAction label="SMS history" icon="list-outline" onPress={() => navigation.navigate('SmsHistory')} />
          <QuickAction label="Templates" icon="document-text-outline" onPress={() => navigation.navigate('TemplatesList')} />
        </View>

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Recent announcements
        </Text>
        {recent.length === 0 ? (
          <Text style={{ color: palette.textSecondary }}>No active announcements.</Text>
        ) : (
          recent.map((a) => (
            <Pressable
              key={a.id}
              onPress={() => navigation.navigate('AnnouncementDetail', { announcementId: a.id })}
              style={{ marginBottom: spacing.sm, padding: spacing.sm, borderWidth: StyleSheet.hairlineWidth, borderColor: palette.border, borderRadius: 8 }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{a.title}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }} numberOfLines={2}>
                {a.content}
              </Text>
            </Pressable>
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
