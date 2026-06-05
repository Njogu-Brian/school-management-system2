import { useApprovalList } from '@erp/core';
import type {
  ApprovalItem,
  ApprovalListFilters,
  ApprovalPriority,
  ApprovalSourceType,
  ApprovalStatus,
} from '@erp/core';
import { ApprovalFilters, ApprovalList, ScreenContainer } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { useTheme } from '@erp/ui';
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
  const { palette, fontSizes, spacing } = useTheme();
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

  const cards = useMemo(
    () => (query.data ?? []).map((item) => approvalItemToCard(item, () => onOpenDetail(item))),
    [query.data, onOpenDetail],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.md, textAlign: 'center' }}>
          You don&apos;t have permission to view approvals.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={styles.container}>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginBottom: spacing.xs }}>
        {SECTION_HINT[status]}
      </Text>
      <ApprovalFilters
        status={status}
        priority={priority}
        sourceType={sourceType}
        onStatusChange={setStatus}
        onPriorityChange={setPriority}
        onSourceTypeChange={setSourceType}
      />
      <ApprovalList
        items={cards}
        isLoading={query.isLoading}
        isRefreshing={query.isFetching && !query.isLoading}
        errorMessage={query.isError ? (query.error as Error).message : null}
        onRefresh={() => void query.refetch()}
        onRetry={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, paddingHorizontal: 16 },
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
