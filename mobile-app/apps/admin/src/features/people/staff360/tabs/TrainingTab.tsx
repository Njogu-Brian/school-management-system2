import { useStaffTrainingRecords } from '@erp/core';
import { EmptyState } from '@erp/ui';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { capitalizeStatus, formatDateLabel } from '../../../shared/utils/formatters';

export interface TrainingTabProps {
  staffId: number;
  onOpenRecord?: (recordId: number) => void;
}

export const TrainingTab: React.FC<TrainingTabProps> = ({ staffId, onOpenRecord }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useStaffTrainingRecords(staffId);

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

  const records = query.data ?? [];
  if (records.length === 0) {
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
      {records.map((record) => (
        <Pressable
          key={record.id}
          onPress={() => onOpenRecord?.(record.id)}
          style={[styles.card, { borderColor: palette.border, marginBottom: spacing.xs }]}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{record.training_name}</Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
            {[record.provider, formatDateLabel(record.start_date), capitalizeStatus(record.status)]
              .filter(Boolean)
              .join(' · ')}
          </Text>
        </Pressable>
      ))}
    </>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12 },
});
