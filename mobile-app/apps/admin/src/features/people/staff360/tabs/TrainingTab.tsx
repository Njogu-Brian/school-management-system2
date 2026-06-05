import { useStaffTrainingRecords } from '@erp/core';
import { EmptyState, FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface TrainingTabProps {
  staffId: number;
}

export const TrainingTab: React.FC<TrainingTabProps> = ({ staffId }) => {
  const { colors, palette, fontSizes } = useTheme();
  const query = useStaffTrainingRecords(staffId);

  const rows = useMemo(
    () =>
      (query.data ?? []).map((record) => ({
        label: record.training_name,
        value: [record.training_type, record.start_date, record.status, record.provider]
          .filter(Boolean)
          .join(' · ') || '—',
      })),
    [query.data],
  );

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: 24, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <View style={{ alignItems: 'center', paddingVertical: 16 }}>
        <Text style={{ color: colors.error }}>{(query.error as Error).message}</Text>
        <Pressable onPress={() => void query.refetch()} style={{ marginTop: 8 }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </View>
    );
  }

  if (rows.length === 0) {
    return (
      <EmptyState
        title="No training records"
        message="No professional development records are on file yet."
        icon="school-outline"
      />
    );
  }

  return (
    <>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 8 }}>
        API: GET /staff/{'{id}'}/training-records
      </Text>
      <FinanceFieldSection title="Training & development" rows={rows} />
    </>
  );
};
