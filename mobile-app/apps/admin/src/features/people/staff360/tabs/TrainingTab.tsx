import { useStaffTrainingRecords } from '@erp/core';
import { EmptyState, useTheme } from '@erp/ui';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { capitalizeStatus, formatDateLabel } from '../../../shared/utils/formatters';

export interface TrainingTabProps {
  staffId: number;
  onOpenRecord?: (recordId: number) => void;
}

export const TrainingTab: React.FC<TrainingTabProps> = ({ staffId, onOpenRecord }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useStaffTrainingRecords(staffId);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Could not load training"
        message={(query.error as Error).message}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
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
          style={[
            styles.card,
            {
              borderColor: palette.borderSubtle,
              backgroundColor: palette.surfaceRaised,
              borderRadius: radius.card,
              padding: spacing.md,
              marginBottom: spacing.sm,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: '600',
              fontSize: typography.body.fontSize,
            }}
          >
            {record.training_name}
          </Text>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              marginTop: 4,
            }}
          >
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
  card: { borderWidth: StyleSheet.hairlineWidth },
});
