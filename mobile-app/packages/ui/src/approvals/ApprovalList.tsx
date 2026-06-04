import React from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { ApprovalCard } from './ApprovalCard';
import type { ApprovalCardData } from './types';

export interface ApprovalListProps {
  items: ApprovalCardData[];
  isLoading?: boolean;
  isRefreshing?: boolean;
  errorMessage?: string | null;
  emptyMessage?: string;
  onRefresh?: () => void;
  onRetry?: () => void;
}

export const ApprovalList: React.FC<ApprovalListProps> = ({
  items,
  isLoading,
  isRefreshing,
  errorMessage,
  emptyMessage = 'No approvals match your filters.',
  onRefresh,
  onRetry,
}) => {
  const { palette, colors, spacing, fontSizes } = useTheme();

  if (isLoading && items.length === 0) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} />
        <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, fontSize: fontSizes.sm }}>
          Loading approvals…
        </Text>
      </View>
    );
  }

  if (errorMessage && items.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={{ color: colors.error, fontSize: fontSizes.sm, textAlign: 'center' }}>
          {errorMessage}
        </Text>
        {onRetry ? (
          <Pressable onPress={onRetry} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.primary, fontWeight: '600', fontSize: fontSizes.sm }}>
              Retry
            </Text>
          </Pressable>
        ) : null}
      </View>
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
        <View style={styles.centered}>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, textAlign: 'center' }}>
            {emptyMessage}
          </Text>
        </View>
      }
    />
  );
};

const styles = StyleSheet.create({
  centered: { paddingVertical: 32, alignItems: 'center', paddingHorizontal: 24 },
  emptyList: { flexGrow: 1 },
});
