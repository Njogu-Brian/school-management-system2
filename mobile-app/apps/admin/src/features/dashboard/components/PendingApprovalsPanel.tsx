import { useApprovalList } from '@erp/core';
import { ApprovalCard, DashboardSection, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { useCanViewApprovals } from '../../approvals/hooks/useCanViewApprovals';
import { approvalItemToCard } from '../../approvals/utils/mapToCard';

const PANEL_LIMIT = 5;

export const PendingApprovalsPanel: React.FC = () => {
  const canView = useCanViewApprovals();
  const navigation = useNavigation<StackNavigationProp<DashboardStackParamList>>();
  const { colors, palette, spacing, fontSizes } = useTheme();

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
        <Pressable onPress={() => navigation.navigate('ApprovalCenter')}>
          <Text style={{ color: colors.primary, fontWeight: '600', fontSize: fontSizes.sm }}>
            View all
          </Text>
        </Pressable>
      }
    >
      {query.isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator color={colors.primary} />
        </View>
      ) : null}

      {query.isError ? (
        <View style={styles.centered}>
          <Text style={{ color: colors.error, fontSize: fontSizes.sm }}>
            {(query.error as Error).message}
          </Text>
          <Pressable onPress={() => void query.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.primary, fontWeight: '600', fontSize: fontSizes.sm }}>
              Retry
            </Text>
          </Pressable>
        </View>
      ) : null}

      {!query.isLoading && !query.isError && cards.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
          No pending approvals right now.
        </Text>
      ) : null}

      {cards.map((card) => (
        <ApprovalCard key={card.id} item={card} />
      ))}
    </DashboardSection>
  );
};

const styles = StyleSheet.create({
  centered: { paddingVertical: 16, alignItems: 'center' },
});
