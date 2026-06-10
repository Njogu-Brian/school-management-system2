import { useCan, useInventoryItem } from '@erp/core';
import {
  AcademicScreenHeader,
  FinanceFieldSection,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'InventoryItemDetail'>;

export const InventoryItemDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('operations.view');
  const { palette, spacing } = useTheme();
  const query = useInventoryItem(route.params.itemId, { enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const item = query.data;

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title={item?.name ?? 'Inventory item'} subtitle="Stock detail" onBack={() => navigation.goBack()} />

      {query.isLoading ? (
        <SkeletonListRows variant="card" count={3} />
      ) : query.isError ? (
        <ListEmptyState
          title="Could not load item"
          message={(query.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : item ? (
        <View>
          <StatusBadge
            label={item.is_low_stock ? 'Low stock' : 'In stock'}
            tone={item.is_low_stock ? 'danger' : 'success'}
            style={{ alignSelf: 'flex-start', marginBottom: spacing.md }}
          />
          <FinanceFieldSection
            title="Stock"
            rows={[
              { label: 'Quantity on hand', value: `${item.quantity} ${item.unit ?? ''}`.trim() },
              { label: 'Minimum stock level', value: `${item.min_stock_level} ${item.unit ?? ''}`.trim() },
              {
                label: 'Unit cost',
                value: item.unit_cost != null ? `KES ${item.unit_cost.toLocaleString()}` : '—',
              },
              { label: 'Location', value: item.location ?? '—' },
            ]}
          />
          <FinanceFieldSection
            title="Details"
            rows={[
              { label: 'Category', value: item.category ?? '—' },
              { label: 'Brand', value: item.brand ?? '—' },
              { label: 'Description', value: item.description ?? '—' },
              { label: 'Last updated', value: formatDateTimeLabel(item.updated_at) || '—' },
            ]}
          />
          <Text style={{ color: palette.textMuted, marginTop: spacing.md, fontSize: 12 }}>
            Stock adjustments are managed on the web portal.
          </Text>
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
