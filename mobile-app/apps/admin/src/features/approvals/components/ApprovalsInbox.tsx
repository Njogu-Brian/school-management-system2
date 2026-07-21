import { useApprovalList } from '@erp/core';
import type {
  ApprovalItem,
  ApprovalListFilters,
  ApprovalPriority,
  ApprovalSourceType,
  ApprovalStatus,
} from '@erp/core';
import {
  ApprovalFilters,
  ApprovalList,
  countActiveFilters,
  EmptyState,
  FilterBottomSheet,
  FilterTriggerButton,
  ScreenContainer,
  SearchBar,
  useTheme,
} from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { approvalItemToCard } from '../utils/mapToCard';

const SECTION_HINT: Record<ApprovalStatus | 'all', string> = {
  all: 'All workflows',
  pending: 'Awaiting your decision',
  approved: 'Recently approved items',
  rejected: 'Declined requests',
  escalated: 'Overdue or escalated',
  expired: 'Past effective date',
};

export interface ApprovalsInboxProps {
  canView: boolean;
  onOpenDetail: (item: ApprovalItem) => void;
  initialStatus?: ApprovalStatus | 'all';
}

export const ApprovalsInbox: React.FC<ApprovalsInboxProps> = ({
  canView,
  onOpenDetail,
  initialStatus = 'pending',
}) => {
  const { palette, spacing, typography } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [searchInput, setSearchInput] = useState('');
  const [status, setStatus] = useState<ApprovalStatus | 'all'>(initialStatus);
  const [priority, setPriority] = useState<ApprovalPriority | 'all'>('all');
  const [sourceType, setSourceType] = useState<ApprovalSourceType | 'all'>('all');

  const filters: ApprovalListFilters = useMemo(
    () => ({ status, priority, sourceType }),
    [status, priority, sourceType],
  );

  const query = useApprovalList({
    filters,
    enabled: canView,
    includeAdmissions: true,
  });

  const cards = useMemo(() => {
    const all = (query.data ?? []).map((item) => approvalItemToCard(item, () => onOpenDetail(item)));
    if (!searchInput.trim()) return all;
    const q = searchInput.trim().toLowerCase();
    return all.filter(
      (c) =>
        c.title.toLowerCase().includes(q) ||
        c.subtitle.toLowerCase().includes(q) ||
        c.sourceLabel?.toLowerCase().includes(q),
    );
  }, [query.data, onOpenDetail, searchInput]);

  const activeFilterCount = countActiveFilters([status, priority, sourceType]);
  /** Pending/all with no refining filters → celebration empty; otherwise offer Clear filters. */
  const isInboxQueueView =
    (status === 'pending' || status === 'all') &&
    priority === 'all' &&
    sourceType === 'all' &&
    !searchInput.trim();

  const clearFilters = () => {
    setStatus('all');
    setPriority('all');
    setSourceType('all');
    setSearchInput('');
    setFiltersOpen(false);
  };

  if (!canView) {
    return (
      <ScreenContainer
        contentContainerStyle={[styles.denied, { padding: spacing.lg }]}
      >
        <EmptyState
          title="Access denied"
          message="You don't have permission to view approvals."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={styles.container}>
      <View
        style={[
          styles.sticky,
          {
            paddingHorizontal: spacing.md,
            paddingTop: spacing.sm,
            paddingBottom: spacing.sm,
            backgroundColor: palette.surface,
            borderBottomColor: palette.borderSubtle,
            gap: spacing.sm,
          },
        ]}
      >
        <SearchBar value={searchInput} onChangeText={setSearchInput} placeholder="Search approvals…" />
        <FilterTriggerButton activeCount={activeFilterCount} onPress={() => setFiltersOpen(true)} />
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
          {SECTION_HINT[status]}
        </Text>
      </View>

      <View style={{ flex: 1, paddingHorizontal: spacing.md }}>
        <ApprovalList
          items={cards}
          isLoading={query.isLoading}
          isRefreshing={query.isFetching && !query.isLoading}
          errorMessage={query.isError ? (query.error as Error).message : null}
          onRefresh={() => void query.refetch()}
          onRetry={() => void query.refetch()}
          onClearFilters={isInboxQueueView ? undefined : clearFilters}
        />
      </View>

      <FilterBottomSheet
        visible={filtersOpen}
        onClose={() => setFiltersOpen(false)}
        onApply={() => setFiltersOpen(false)}
        onClear={clearFilters}
      >
        <ApprovalFilters
          status={status}
          priority={priority}
          sourceType={sourceType}
          onStatusChange={setStatus}
          onPriorityChange={setPriority}
          onSourceTypeChange={setSourceType}
        />
      </FilterBottomSheet>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1 },
  sticky: { zIndex: 2, borderBottomWidth: StyleSheet.hairlineWidth },
  denied: { flex: 1, justifyContent: 'center' },
});
