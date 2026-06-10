import { useAsset, useCan, useUpdateAssetStatus } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  FinanceFieldSection,
  ScreenContainer,
  StatusBadge,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { capitalizeStatus, formatDateLabel, formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'AssetDetail'>;

type AssetStatus = 'active' | 'in_repair' | 'retired' | 'disposed';

const STATUS_OPTIONS: Array<{ value: AssetStatus; label: string }> = [
  { value: 'active', label: 'Active' },
  { value: 'in_repair', label: 'In repair' },
  { value: 'retired', label: 'Retired' },
  { value: 'disposed', label: 'Disposed' },
];

const statusTone = (status?: string | null) => {
  switch (status) {
    case 'active':
      return 'success' as const;
    case 'in_repair':
      return 'warning' as const;
    case 'retired':
    case 'disposed':
      return 'danger' as const;
    default:
      return 'info' as const;
  }
};

export const AssetDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { assetId } = route.params;
  const canView = useCan('operations.view');
  const { colors, palette, spacing, typography } = useTheme();
  const query = useAsset(assetId, { enabled: canView });
  const statusMutation = useUpdateAssetStatus();
  const [pendingStatus, setPendingStatus] = useState<AssetStatus | null>(null);
  const [statusNotes, setStatusNotes] = useState('');

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (query.isError || !query.data) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error }}>{(query.error as Error)?.message ?? 'Not found'}</Text>
        <Pressable onPress={() => void query.refetch()}>
          <Text style={{ color: colors.primary, marginTop: 12 }}>Retry</Text>
        </Pressable>
      </ScreenContainer>
    );
  }

  const asset = query.data;
  const currentStatus = (asset.status as AssetStatus) ?? 'active';
  const selectedStatus = pendingStatus ?? currentStatus;
  const statusChanged = selectedStatus !== currentStatus;

  const onSaveStatus = async () => {
    if (!statusChanged) return;
    try {
      await statusMutation.mutateAsync({
        id: assetId,
        status: selectedStatus,
        notes: statusNotes.trim() || undefined,
      });
      setPendingStatus(null);
      setStatusNotes('');
      showSuccess('Status updated', `Asset marked as ${capitalizeStatus(selectedStatus)}.`);
    } catch (err) {
      showError('Update failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title={asset.name} onBack={() => navigation.goBack()} />
      <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: spacing.md }}>
        <StatusBadge label={capitalizeStatus(asset.status)} tone={statusTone(asset.status)} />
        <Button label="Edit" variant="secondary" onPress={() => navigation.navigate('AssetForm', { assetId })} />
      </View>
      <FinanceFieldSection
        title="Asset"
        rows={[
          { label: 'Asset code', value: asset.asset_tag ?? '—' },
          { label: 'Category', value: asset.category ?? '—' },
          { label: 'Location', value: asset.location ?? '—' },
          { label: 'Status', value: capitalizeStatus(asset.status) },
          { label: 'Assigned to', value: asset.assigned_to ?? '—' },
          { label: 'Serial', value: asset.serial_number ?? '—' },
          { label: 'Purchase date', value: formatDateLabel(asset.purchase_date) },
          { label: 'Cost', value: formatKes(asset.purchase_cost) },
        ]}
      />
      {asset.notes ? (
        <Text style={{ color: palette.textSecondary, marginTop: spacing.md }}>{asset.notes}</Text>
      ) : null}

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
        UPDATE STATUS
      </Text>
      <FilterChipRow>
        {STATUS_OPTIONS.map((opt) => (
          <FilterChip
            key={opt.value}
            label={opt.label}
            active={selectedStatus === opt.value}
            onPress={() => setPendingStatus(opt.value)}
          />
        ))}
      </FilterChipRow>
      {statusChanged ? (
        <View>
          <TextField
            label="Notes (optional)"
            value={statusNotes}
            onChangeText={setStatusNotes}
            placeholder="Reason for status change"
          />
          <Button
            label={statusMutation.isPending ? 'Saving…' : `Mark as ${capitalizeStatus(selectedStatus)}`}
            onPress={() => void onSaveStatus()}
            disabled={statusMutation.isPending}
            loading={statusMutation.isPending}
          />
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
});
