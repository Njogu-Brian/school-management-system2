import React from 'react';
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
import { ListEmptyState, QueueEmptyState } from '../feedback/ListEmptyState';
import { SkeletonListRows } from '../feedback/SkeletonListRows';
import { useTheme } from '../theme/ThemeContext';
import { ApprovalCard } from './ApprovalCard';
import type { ApprovalCardData } from './types';

export interface ApprovalListProps {
  items: ApprovalCardData[];
  isLoading?: boolean;
  isRefreshing?: boolean;
  errorMessage?: string | null;
  emptyMessage?: string;
  emptyTitle?: string;
  onRefresh?: () => void;
  onRetry?: () => void;
  onClearFilters?: () => void;
}

export const ApprovalList: React.FC<ApprovalListProps> = ({
  items,
  isLoading,
  isRefreshing,
  errorMessage,
  emptyMessage = 'No approvals match your filters.',
  emptyTitle = 'No approvals found',
  onRefresh,
  onRetry,
  onClearFilters,
}) => {
  const { colors, spacing } = useTheme();

  if (isLoading && items.length === 0) {
    return (
      <View style={[styles.centered, { paddingVertical: spacing.xl, paddingHorizontal: spacing.lg }]}>
        <SkeletonListRows count={5} variant="compact" />
      </View>
    );
  }

  if (errorMessage && items.length === 0) {
    return (
      <EmptyState
        title="Could not load approvals"
        message={errorMessage}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={onRetry}
      />
    );
  }

  return (
    <FlatList
      data={items}
      keyExtractor={(item) => item.id}
      renderItem={({ item }) => <ApprovalCard item={item} />}
      contentContainerStyle={items.length === 0 ? styles.emptyList : undefined}
      refreshControl={
        onRefresh ? (
          <RefreshControl
            refreshing={Boolean(isRefreshing)}
            onRefresh={onRefresh}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        ) : undefined
      }
      ListEmptyComponent={
        onClearFilters ? (
          <ListEmptyState
            title={emptyTitle}
            message={emptyMessage}
            icon="filter-outline"
            onClearFilters={onClearFilters}
          />
        ) : (
          <QueueEmptyState
            title="All caught up"
            message="You're all caught up — nothing needs your action right now."
          />
        )
      }
    />
  );
};

const styles = StyleSheet.create({
  centered: { alignItems: 'center' },
  emptyList: { flexGrow: 1 },
});
