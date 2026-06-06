import React from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { ListEmptyState } from '../feedback/ListEmptyState';
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
  const { colors } = useTheme();

  if (isLoading && items.length === 0) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (errorMessage && items.length === 0) {
    return (
      <ListEmptyState
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
        <ListEmptyState
          title={emptyTitle}
          message={emptyMessage}
          icon={onClearFilters ? 'filter-outline' : 'checkmark-circle-outline'}
          onClearFilters={onClearFilters}
        />
      }
    />
  );
};

const styles = StyleSheet.create({
  centered: { paddingVertical: 32, alignItems: 'center', paddingHorizontal: 24 },
  emptyList: { flexGrow: 1 },
});
