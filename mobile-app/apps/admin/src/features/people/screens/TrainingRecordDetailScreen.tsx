import { useCan, useStaffTrainingRecord } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { capitalizeStatus, formatDateLabel, formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<PeopleStackParamList, 'TrainingRecordDetail'>;

export const TrainingRecordDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { staffId, recordId } = route.params;
  const canView = useCan('people.view');
  const { colors, palette, spacing } = useTheme();
  const query = useStaffTrainingRecord(staffId, recordId, { enabled: canView });

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

  const record = query.data;

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={record.training_name} onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Training"
        rows={[
          { label: 'Provider', value: record.provider ?? '—' },
          { label: 'Location', value: record.location ?? '—' },
          { label: 'Start', value: formatDateLabel(record.start_date) },
          { label: 'End', value: formatDateLabel(record.end_date) },
          { label: 'Duration (hrs)', value: record.duration_hours != null ? String(record.duration_hours) : '—' },
          { label: 'Type', value: capitalizeStatus(record.training_type) },
          { label: 'Status', value: capitalizeStatus(record.status) },
          { label: 'Certificate #', value: record.certificate_number ?? '—' },
          { label: 'Cost', value: formatKes(record.cost) },
        ]}
      />
      {record.description ? (
        <FinanceFieldSection title="Description" rows={[{ label: 'Details', value: record.description }]} />
      ) : null}
      {record.certificate_file ? (
        <Text style={{ color: palette.textSecondary, fontSize: 12, marginTop: spacing.md }}>
          Certificate file on record — download via web portal until mobile certificate endpoint is added.
        </Text>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
});
