import { useAsset, useCan } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { capitalizeStatus, formatDateLabel, formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'AssetDetail'>;

export const AssetDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { assetId } = route.params;
  const canView = useCan('operations.view');
  const { colors, palette, spacing } = useTheme();
  const query = useAsset(assetId, { enabled: canView });

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

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={asset.name} onBack={() => navigation.goBack()} />
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
      <Text style={{ color: palette.textSecondary, fontSize: 12, marginTop: spacing.lg }}>
        Assign / reassign / return actions are available on the web portal.
      </Text>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
});
