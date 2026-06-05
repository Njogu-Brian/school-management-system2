import { useApprovalList } from '@erp/core';
import type { ApprovalListFilters, ApprovalPriority, ApprovalSourceType, ApprovalStatus } from '@erp/core';
import { ApprovalFilters, ApprovalList, ScreenContainer } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { useTheme } from '@erp/ui';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { useCanViewApprovals } from '../hooks/useCanViewApprovals';
import { approvalItemToCard } from '../utils/mapToCard';

export const ApprovalCenterScreen: React.FC = () => {
  const canView = useCanViewApprovals();
  const navigation = useNavigation<StackNavigationProp<DashboardStackParamList>>();
  const { palette, fontSizes } = useTheme();

  const [status, setStatus] = useState<ApprovalStatus | 'all'>('pending');
  const [priority, setPriority] = useState<ApprovalPriority | 'all'>('all');
  const [sourceType, setSourceType] = useState<ApprovalSourceType | 'all'>('all');

  const filters: ApprovalListFilters = useMemo(
    () => ({ status, priority, sourceType }),
    [status, priority, sourceType],
  );

  const query = useApprovalList({
    filters,
    enabled: canView,
  });

  const cards = useMemo(
    () =>
      (query.data ?? []).map((item) =>
        approvalItemToCard(item, () =>
          navigation.navigate('ApprovalDetail', { id: item.id, item }),
        ),
      ),
    [query.data, navigation],
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
