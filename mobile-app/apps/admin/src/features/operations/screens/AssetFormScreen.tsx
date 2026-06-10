import { useAsset, useCan, useCreateAsset, useInfiniteStaffList, useUpdateAsset } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterBottomSheet,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'AssetForm'>;

export const AssetFormScreen: React.FC<Props> = ({ navigation, route }) => {
  const editId = route.params?.assetId;
  const isEdit = editId != null && editId > 0;
  const canView = useCan('operations.view');
  const { palette, spacing, typography } = useTheme();
  const detailQuery = useAsset(editId ?? 0, { enabled: isEdit && canView });
  const createMutation = useCreateAsset();
  const updateMutation = useUpdateAsset();

  const [assetTag, setAssetTag] = useState('');
  const [name, setName] = useState('');
  const [category, setCategory] = useState('');
  const [location, setLocation] = useState('');
  const [serialNumber, setSerialNumber] = useState('');
  const [purchaseDate, setPurchaseDate] = useState('');
  const [purchaseCost, setPurchaseCost] = useState('');
  const [notes, setNotes] = useState('');
  const [assignedStaff, setAssignedStaff] = useState<{ id: number; name: string } | null>(null);

  const [staffPickerOpen, setStaffPickerOpen] = useState(false);
  const [staffSearch, setStaffSearch] = useState('');
  const staffQuery = useInfiniteStaffList(
    {
      search: staffSearch.trim() || undefined,
      departmentId: null,
      staffCategoryId: null,
      employmentStatus: 'all',
      gender: 'all',
      role: null,
      perPage: 25,
    },
    { enabled: staffPickerOpen },
  );
  const staffRows = useMemo(() => staffQuery.data?.pages.flatMap((p) => p.items) ?? [], [staffQuery.data]);

  useEffect(() => {
    const a = detailQuery.data;
    if (a) {
      setAssetTag(a.asset_tag ?? '');
      setName(a.name ?? '');
      setCategory(a.category ?? '');
      setLocation(a.location ?? '');
      setSerialNumber(a.serial_number ?? '');
      setPurchaseDate(a.purchase_date ?? '');
      setPurchaseCost(a.purchase_cost != null ? String(a.purchase_cost) : '');
      setNotes(a.notes ?? '');
      setAssignedStaff(
        a.assigned_staff_id != null ? { id: a.assigned_staff_id, name: a.assigned_to ?? `Staff #${a.assigned_staff_id}` } : null,
      );
    }
  }, [detailQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (isEdit && detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Edit asset" onBack={() => navigation.goBack()} />
        <SkeletonListRows count={6} variant="compact" />
      </ScreenContainer>
    );
  }

  const canSubmit = assetTag.trim().length > 0 && name.trim().length > 0;
  const pending = createMutation.isPending || updateMutation.isPending;

  const onSave = async () => {
    if (!canSubmit) {
      showError('Validation', 'Asset tag and name are required.');
      return;
    }
    const cost = purchaseCost.trim() ? Number(purchaseCost) : undefined;
    if (cost !== undefined && (!Number.isFinite(cost) || cost < 0)) {
      showError('Validation', 'Purchase cost must be a positive number.');
      return;
    }
    const payload = {
      asset_tag: assetTag.trim(),
      name: name.trim(),
      category: category.trim() || undefined,
      location: location.trim() || undefined,
      serial_number: serialNumber.trim() || undefined,
      purchase_date: purchaseDate.trim() || undefined,
      purchase_cost: cost,
      assigned_staff_id: assignedStaff?.id ?? null,
      notes: notes.trim() || undefined,
    };
    try {
      if (isEdit && editId) {
        await updateMutation.mutateAsync({ id: editId, ...payload });
        showSuccess('Updated', 'Asset saved.', () => navigation.goBack());
      } else {
        await createMutation.mutateAsync(payload);
        showSuccess('Registered', 'Asset registered.', () => navigation.goBack());
      }
    } catch (err) {
      showError('Save failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={isEdit ? 'Edit asset' : 'Register asset'}
        subtitle="Fixed asset register"
        onBack={() => navigation.goBack()}
      />

      <TextField label="Asset tag" value={assetTag} onChangeText={setAssetTag} placeholder="e.g. LAP-0042" />
      <TextField label="Name" value={name} onChangeText={setName} placeholder="e.g. Dell Latitude 5440" />
      <TextField label="Category (optional)" value={category} onChangeText={setCategory} placeholder="e.g. ICT equipment" />
      <TextField label="Location (optional)" value={location} onChangeText={setLocation} placeholder="e.g. Staff room" />
      <TextField label="Serial number (optional)" value={serialNumber} onChangeText={setSerialNumber} placeholder="Manufacturer serial" />
      <View style={{ flexDirection: 'row', gap: spacing.sm }}>
        <View style={{ flex: 1 }}>
          <TextField label="Purchase date" value={purchaseDate} onChangeText={setPurchaseDate} placeholder="YYYY-MM-DD" />
        </View>
        <View style={{ flex: 1 }}>
          <TextField label="Cost (KES)" value={purchaseCost} onChangeText={setPurchaseCost} placeholder="0" keyboardType="numeric" />
        </View>
      </View>

      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, fontWeight: '700', letterSpacing: 0.4, marginTop: spacing.sm, marginBottom: 6 }}>
        ASSIGNED TO
      </Text>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
        <Text style={{ color: palette.textPrimary, flex: 1 }}>{assignedStaff?.name ?? 'Unassigned'}</Text>
        <Button label={assignedStaff ? 'Change' : 'Assign staff'} variant="secondary" onPress={() => setStaffPickerOpen(true)} />
        {assignedStaff ? <Button label="Clear" variant="ghost" onPress={() => setAssignedStaff(null)} /> : null}
      </View>

      <TextField label="Notes (optional)" value={notes} onChangeText={setNotes} placeholder="Condition, warranty, remarks…" multiline numberOfLines={3} textAlignVertical="top" />

      <View style={{ marginTop: spacing.lg }}>
        <Button
          label={pending ? 'Saving…' : isEdit ? 'Save changes' : 'Register asset'}
          onPress={() => void onSave()}
          disabled={!canSubmit || pending}
          loading={pending}
        />
      </View>

      <FilterBottomSheet
        visible={staffPickerOpen}
        onClose={() => setStaffPickerOpen(false)}
        title="Assign to staff"
        onApply={() => setStaffPickerOpen(false)}
        onClear={() => setStaffSearch('')}
      >
        <SearchBar value={staffSearch} onChangeText={setStaffSearch} placeholder="Search staff…" />
        <ScrollView style={{ maxHeight: 360, marginTop: spacing.sm }}>
          {staffRows.map((s) => (
            <Pressable
              key={s.id}
              onPress={() => {
                setAssignedStaff({ id: s.id, name: s.fullName });
                setStaffPickerOpen(false);
              }}
              style={({ pressed }) => ({
                paddingVertical: spacing.sm,
                borderBottomWidth: StyleSheet.hairlineWidth,
                borderBottomColor: palette.borderSubtle,
                opacity: pressed ? 0.7 : 1,
              })}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {[s.jobTitle, s.departmentName].filter(Boolean).join(' · ') || s.employeeNumber}
              </Text>
            </Pressable>
          ))}
          {staffQuery.isLoading ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>Loading…</Text>
          ) : null}
          {!staffQuery.isLoading && staffRows.length === 0 ? (
            <Text style={{ color: palette.textMuted, paddingVertical: spacing.sm }}>No matching staff.</Text>
          ) : null}
        </ScrollView>
      </FilterBottomSheet>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
