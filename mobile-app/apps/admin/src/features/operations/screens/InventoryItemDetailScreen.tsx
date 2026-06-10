import { useAdjustInventoryStock, useCan, useInventoryItem } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  FinanceFieldSection,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'InventoryItemDetail'>;

type AdjustType = 'in' | 'out' | 'adjustment';

const ADJUST_OPTIONS: Array<{ value: AdjustType; label: string }> = [
  { value: 'in', label: 'Receive (+)' },
  { value: 'out', label: 'Issue (−)' },
  { value: 'adjustment', label: 'Set quantity' },
];

export const InventoryItemDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('operations.view');
  const { palette, spacing, typography } = useTheme();
  const query = useInventoryItem(route.params.itemId, { enabled: canView });
  const adjustMutation = useAdjustInventoryStock();

  const [adjustType, setAdjustType] = useState<AdjustType>('in');
  const [quantity, setQuantity] = useState('');
  const [notes, setNotes] = useState('');

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const item = query.data;
  const parsedQty = Number(quantity);
  const qtyValid = quantity.trim().length > 0 && Number.isFinite(parsedQty) && parsedQty >= 0;

  const onApply = async () => {
    if (!item || !qtyValid) {
      showError('Validation', 'Enter a valid quantity.');
      return;
    }
    try {
      await adjustMutation.mutateAsync({
        id: item.id,
        type: adjustType,
        quantity: parsedQty,
        notes: notes.trim() || undefined,
      });
      setQuantity('');
      setNotes('');
      showSuccess('Stock updated', 'The stock level has been updated.');
    } catch (err) {
      showError('Update failed', (err as Error).message);
    }
  };

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

          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              fontWeight: '700',
              letterSpacing: 0.4,
              marginTop: spacing.lg,
              marginBottom: spacing.xs,
            }}
          >
            ADJUST STOCK
          </Text>
          <FilterChipRow>
            {ADJUST_OPTIONS.map((opt) => (
              <FilterChip
                key={opt.value}
                label={opt.label}
                active={adjustType === opt.value}
                onPress={() => setAdjustType(opt.value)}
              />
            ))}
          </FilterChipRow>
          <TextField
            label={adjustType === 'adjustment' ? `New quantity (${item.unit ?? 'units'})` : `Quantity (${item.unit ?? 'units'})`}
            value={quantity}
            onChangeText={setQuantity}
            placeholder="0"
            keyboardType="numeric"
          />
          <TextField label="Notes (optional)" value={notes} onChangeText={setNotes} placeholder="Reason for adjustment" />
          <Button
            label={adjustMutation.isPending ? 'Updating…' : 'Apply adjustment'}
            onPress={() => void onApply()}
            disabled={!qtyValid || adjustMutation.isPending}
            loading={adjustMutation.isPending}
          />
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
