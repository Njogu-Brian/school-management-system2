import { useCan, useCreateRequisition, useInventoryItems } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterBottomSheet,
  FinanceFieldSection,
  ScreenContainer,
  SearchBar,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'RequisitionForm'>;

interface DraftItem {
  key: string;
  inventory_item_id?: number;
  item_name: string;
  brand?: string;
  quantity_requested: number;
  unit: string;
}

export const RequisitionFormScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const createMutation = useCreateRequisition();

  const [purpose, setPurpose] = useState('');
  const [items, setItems] = useState<DraftItem[]>([]);

  const [itemName, setItemName] = useState('');
  const [brand, setBrand] = useState('');
  const [qty, setQty] = useState('');
  const [unit, setUnit] = useState('pcs');
  const [linkedInventoryId, setLinkedInventoryId] = useState<number | undefined>(undefined);

  const [pickerOpen, setPickerOpen] = useState(false);
  const [pickerSearch, setPickerSearch] = useState('');
  const inventoryQuery = useInventoryItems({ enabled: pickerOpen, search: pickerSearch || undefined });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const parsedQty = Number(qty);
  const itemValid = itemName.trim().length > 0 && Number.isFinite(parsedQty) && parsedQty > 0 && unit.trim().length > 0;

  const addItem = () => {
    if (!itemValid) {
      showError('Validation', 'Item name, a positive quantity and a unit are required.');
      return;
    }
    setItems((prev) => [
      ...prev,
      {
        key: `${Date.now()}-${prev.length}`,
        inventory_item_id: linkedInventoryId,
        item_name: itemName.trim(),
        brand: brand.trim() || undefined,
        quantity_requested: parsedQty,
        unit: unit.trim(),
      },
    ]);
    setItemName('');
    setBrand('');
    setQty('');
    setUnit('pcs');
    setLinkedInventoryId(undefined);
  };

  const onSubmit = async () => {
    if (items.length === 0) {
      showError('Validation', 'Add at least one item to the requisition.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        type: 'inventory',
        purpose: purpose.trim() || undefined,
        items: items.map(({ key: _key, ...rest }) => rest),
      });
      showSuccess('Submitted', 'Requisition submitted for approval.', () => navigation.goBack());
    } catch (err) {
      showError('Submit failed', (err as Error).message);
    }
  };

  const sectionLabel = {
    color: palette.textSecondary,
    fontSize: typography.caption.fontSize,
    fontWeight: '700' as const,
    letterSpacing: 0.4,
    marginTop: spacing.lg,
    marginBottom: spacing.xs,
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="New requisition"
        subtitle="Request supplies from stores"
        onBack={() => navigation.goBack()}
      />

      <TextField
        label="Purpose (optional)"
        value={purpose}
        onChangeText={setPurpose}
        placeholder="e.g. Term 2 classroom supplies"
      />

      {items.length > 0 ? (
        <FinanceFieldSection
          title={`Items (${items.length})`}
          rows={items.map((it) => ({
            label: `${it.item_name}${it.brand ? ` (${it.brand})` : ''}`,
            value: `${it.quantity_requested} ${it.unit}`,
          }))}
        />
      ) : null}
      {items.length > 0 ? (
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs, marginTop: spacing.xs }}>
          {items.map((it) => (
            <Pressable
              key={it.key}
              onPress={() => setItems((prev) => prev.filter((p) => p.key !== it.key))}
              style={{
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: palette.borderSubtle,
                borderRadius: radius.md,
                paddingHorizontal: spacing.sm,
                paddingVertical: 6,
                backgroundColor: palette.surfaceRaised,
              }}
            >
              <Text style={{ color: colors.error, fontSize: typography.caption.fontSize }}>
                Remove {it.item_name} ✕
              </Text>
            </Pressable>
          ))}
        </View>
      ) : null}

      <Text style={sectionLabel}>ADD ITEM</Text>
      <Button label="Pick from inventory" variant="ghost" onPress={() => setPickerOpen(true)} />
      <TextField label="Item name" value={itemName} onChangeText={setItemName} placeholder="e.g. Whiteboard markers" />
      <TextField label="Brand (optional)" value={brand} onChangeText={setBrand} placeholder="Preferred brand" />
      <View style={{ flexDirection: 'row', gap: spacing.sm }}>
        <View style={{ flex: 1 }}>
          <TextField label="Quantity" value={qty} onChangeText={setQty} placeholder="0" keyboardType="numeric" />
        </View>
        <View style={{ flex: 1 }}>
          <TextField label="Unit" value={unit} onChangeText={setUnit} placeholder="pcs, boxes…" />
        </View>
      </View>
      <Button label="Add item" variant="secondary" onPress={addItem} disabled={!itemValid} />

      <View style={{ marginTop: spacing.lg }}>
        <Button
          label={createMutation.isPending ? 'Submitting…' : `Submit requisition${items.length ? ` (${items.length} item${items.length === 1 ? '' : 's'})` : ''}`}
          onPress={() => void onSubmit()}
          disabled={items.length === 0 || createMutation.isPending}
          loading={createMutation.isPending}
        />
      </View>

      <FilterBottomSheet
        visible={pickerOpen}
        onClose={() => setPickerOpen(false)}
        title="Pick from inventory"
        onApply={() => setPickerOpen(false)}
        onClear={() => setPickerSearch('')}
      >
        <SearchBar value={pickerSearch} onChangeText={setPickerSearch} placeholder="Search inventory…" />
        <ScrollView style={{ maxHeight: 360, marginTop: spacing.sm }}>
          {(inventoryQuery.data ?? []).map((inv) => (
            <Pressable
              key={inv.id}
              onPress={() => {
                setItemName(inv.name);
                setBrand(inv.brand ?? '');
                setUnit(inv.unit ?? 'pcs');
                setLinkedInventoryId(inv.id);
                setPickerOpen(false);
              }}
              style={({ pressed }) => ({
                paddingVertical: spacing.sm,
                borderBottomWidth: StyleSheet.hairlineWidth,
                borderBottomColor: palette.borderSubtle,
                opacity: pressed ? 0.7 : 1,
              })}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{inv.name}</Text>
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {inv.quantity} {inv.unit ?? ''} in stock{inv.is_low_stock ? ' · low stock' : ''}
              </Text>
            </Pressable>
          ))}
          {inventoryQuery.isLoading ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>Loading…</Text>
          ) : null}
          {!inventoryQuery.isLoading && (inventoryQuery.data ?? []).length === 0 ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>No matching items.</Text>
          ) : null}
        </ScrollView>
      </FilterBottomSheet>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
