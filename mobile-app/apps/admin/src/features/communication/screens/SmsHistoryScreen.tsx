import { useCan, useInfiniteCommunicationLogs } from '@erp/core';
import {
  AcademicScreenHeader,
  countActiveFilters,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
} from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { capitalizeStatus, formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsHistory'>;

const STATUS_FILTERS = ['all', 'sent', 'delivered', 'failed', 'pending'] as const;
type StatusFilter = (typeof STATUS_FILTERS)[number];

const STATUS_TONES: Record<string, 'success' | 'danger' | 'warning' | 'info'> = {
  delivered: 'success',
  sent: 'info',
  failed: 'danger',
  pending: 'warning',
};

export const SmsHistoryScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [search, setSearch] = useState('');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const listQuery = useInfiniteCommunicationLogs({
    enabled: canView,
    channel: 'sms',
    status: statusFilter === 'all' ? undefined : statusFilter,
  });

  const items = useMemo(() => {
    const all = listQuery.data?.pages.flatMap((p) => p.items) ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return all;
    return all.filter(
      (l) =>
        (l.contact ?? '').toLowerCase().includes(q) ||
        (l.title ?? '').toLowerCase().includes(q) ||
        (l.message ?? '').toLowerCase().includes(q),
    );
  }, [listQuery.data, search]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need communication.view permission to view SMS history."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={items}
        keyExtractor={(item) => String(item.id)}
        hero={<AcademicScreenHeader title="SMS history" subtitle="Delivery log" onBack={() => navigation.goBack()} />}
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search contact or message…" />}
        activeFilterCount={countActiveFilters([statusFilter])}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={() => {
          setStatusFilter('all');
          setSearch('');
          setFiltersOpen(false);
        }}
        filterContent={
          <FilterChipRow label="Status">
            {STATUS_FILTERS.map((s) => (
              <FilterChip
                key={s}
                label={capitalizeStatus(s)}
                active={statusFilter === s}
                onPress={() => setStatusFilter(s)}
              />
            ))}
          </FilterChipRow>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('SmsLogDetail', { logId: item.id })}
            style={({ pressed }) => [
              elevation[1],
              {
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: palette.borderSubtle,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
              {item.contact ?? item.title ?? 'SMS'}
            </Text>
            <Text
              style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}
              numberOfLines={2}
            >
              {item.message ?? '—'}
            </Text>
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
              {formatDateTimeLabel(item.sent_at ?? item.created_at)}
            </Text>
            {item.status ? (
              <StatusBadge
                label={capitalizeStatus(item.status)}
                tone={STATUS_TONES[item.status] ?? 'info'}
                compact
                style={{ alignSelf: 'flex-start', marginTop: spacing.xs }}
              />
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
            <SkeletonListRows variant="card" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load SMS logs"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No SMS logs"
              message="No messages match your filters."
              icon="chatbubble-outline"
              onClearFilters={() => {
                setStatusFilter('all');
                setSearch('');
              }}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
