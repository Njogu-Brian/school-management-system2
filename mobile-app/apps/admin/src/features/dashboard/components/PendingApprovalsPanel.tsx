import { useApprovalList } from '@erp/core';
import { ApprovalCard, DashboardSection, EmptyState, QueueEmptyState, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';
import { useCanViewApprovals } from '../../approvals/hooks/useCanViewApprovals';
import { approvalItemToCard } from '../../approvals/utils/mapToCard';

const PANEL_LIMIT = 5;

export const PendingApprovalsPanel: React.FC = () => {
  const canView = useCanViewApprovals();
  const navigation = useNavigation<StackNavigationProp<DashboardStackParamList>>();
  const { palette, spacing, typography } = useTheme();

  const query = useApprovalList({
    filters: { status: 'pending', priority: 'all', sourceType: 'all' },
    enabled: canView,
  });

  const items = useMemo(() => (query.data ?? []).slice(0, PANEL_LIMIT), [query.data]);

  const cards = useMemo(
    () =>
      items.map((item) =>
        approvalItemToCard(item, () =>
          navigation.navigate('ApprovalDetail', { id: item.id, item }),
        ),
      ),
    [items, navigation],
  );

  if (!canView) {
    return null;
  }

  return (
    <DashboardSection
      title="Pending approvals"
      subtitle="Leave requests and lesson plans awaiting action"
      headerRight={
        <Pressable onPress={() => navigateToDrawer(navigation, 'Approvals', 'ApprovalsHome')}>
          <Text
            style={{
              color: palette.primary,
              fontWeight: '600',
              fontSize: typography.caption.fontSize,
            }}
          >
            View all
          </Text>
        </Pressable>
      }
    >
      {query.isLoading ? (
        <View style={[styles.centered, { paddingVertical: spacing.md }]}>
          <ActivityIndicator color={palette.primary} />
        </View>
      ) : null}

      {query.isError ? (
        <EmptyState
          title="Couldn’t load approvals"
          message={(query.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : null}

      {!query.isLoading && !query.isError && cards.length === 0 ? (
        <QueueEmptyState
          title="No pending approvals"
          message="You're all caught up — nothing needs your action right now."
        />
      ) : null}

      {cards.map((card) => (
        <ApprovalCard key={card.id} item={card} />
      ))}
    </DashboardSection>
  );
};

const styles = StyleSheet.create({
  centered: { alignItems: 'center' },
});
