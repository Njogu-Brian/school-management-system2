import { useConcernDetail, useUpdateConcern } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'ConcernDetail'>;

const STATUSES = ['open', 'in_progress', 'resolved', 'closed'] as const;

export const ConcernDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { concernId } = route.params;
  const { palette, spacing, typography } = useTheme();
  const query = useConcernDetail(concernId);
  const updateMutation = useUpdateConcern();
  const [status, setStatus] = useState('open');

  useEffect(() => {
    if (query.data?.status) setStatus(query.data.status);
  }, [query.data?.status]);

  const save = async () => {
    try {
      await updateMutation.mutateAsync({ id: concernId, status });
      showSuccess('Updated', 'Concern status saved.');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Update failed.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Concern detail" onBack={() => navigation.goBack()} />
        {query.isLoading ? <ActivityIndicator color={palette.primary} /> : null}
        {query.isError ? (
          <EmptyState
            title="Could not load"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : query.data ? (
          <>
            <Text style={{ color: palette.textPrimary, fontWeight: '800', fontSize: typography.title.fontSize }}>
              {query.data.student_name}
            </Text>
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }}>
              {query.data.category} · {query.data.admission_number}
            </Text>
            <Text style={{ color: palette.textPrimary, marginBottom: spacing.lg }}>{query.data.description}</Text>
            <Text style={{ color: palette.textMuted, marginBottom: spacing.sm }}>
              Staff: {query.data.staff.map((s) => s.name).filter(Boolean).join(', ') || '—'}
            </Text>
            <FilterChipRow label="Status">
              {STATUSES.map((s) => (
                <FilterChip key={s} label={s.replace('_', ' ')} active={status === s} onPress={() => setStatus(s)} />
              ))}
            </FilterChipRow>
            <Button label="Save status" onPress={() => void save()} loading={updateMutation.isPending} />
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};
