import { useCan, useInfiniteCommunicationLogs } from '@erp/core';
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
  View,
} from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { capitalizeStatus, formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsHistory'>;

const STATUS_FILTERS = ['all', 'sent', 'delivered', 'failed', 'pending'] as const;

export const SmsHistoryScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [statusFilter, setStatusFilter] = useState<(typeof STATUS_FILTERS)[number]>('all');

  const listQuery = useInfiniteCommunicationLogs({
    enabled: canView,
    channel: 'sms',
    status: statusFilter === 'all' ? undefined : statusFilter,
  });

  const items = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="SMS history" onBack={() => navigation.goBack()} />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: spacing.sm }}>
              {STATUS_FILTERS.map((s) => (
                <Pressable
                  key={s}
                  onPress={() => setStatusFilter(s)}
                  style={[
                    styles.chip,
                    { borderColor: palette.border },
                    statusFilter === s && { borderColor: colors.primary, backgroundColor: '#E8F0FA' },
                  ]}
                >
                  <Text style={{ fontSize: fontSizes.xs, color: palette.textPrimary }}>{capitalizeStatus(s)}</Text>
                </Pressable>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('SmsLogDetail', { logId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
              {item.contact ?? item.title ?? 'SMS'}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }} numberOfLines={2}>
              {item.message ?? '—'}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
              {[capitalizeStatus(item.status), formatDateTimeLabel(item.sent_at ?? item.created_at)].filter(Boolean).join(' · ')}
            </Text>
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
          ) : listQuery.isError ? (
            <Pressable onPress={() => void listQuery.refetch()}>
              <Text style={{ color: colors.error, textAlign: 'center' }}>Retry</Text>
            </Pressable>
          ) : (
            <EmptyState title="No SMS logs" message="No messages match your filters." icon="chatbubble-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  chip: { borderWidth: 1, borderRadius: 16, paddingHorizontal: 10, paddingVertical: 6 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
