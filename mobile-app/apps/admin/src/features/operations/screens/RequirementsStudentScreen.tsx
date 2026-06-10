import { useCan, useStudentRequirements } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { capitalizeStatus } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'RequirementsStudent'>;

const STATUS_TONES: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
  complete: 'success',
  collected: 'success',
  partial: 'warning',
  pending: 'danger',
};

export const RequirementsStudentScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('operations.view');
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const query = useStudentRequirements(route.params.studentId, { enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const data = query.data;
  const items = data?.items ?? [];
  const collected = items.filter(
    (i) => i.quantity_collected >= i.quantity_required && i.quantity_required > 0,
  ).length;

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={data?.student.full_name ?? route.params.studentName ?? 'Requirements'}
        subtitle={
          data
            ? [data.student.admission_number, data.student.class_name, data.current_term?.name]
                .filter(Boolean)
                .join(' · ')
            : 'Term requirements'
        }
        onBack={() => navigation.goBack()}
      />

      {query.isLoading ? (
        <SkeletonListRows variant="card" />
      ) : query.isError ? (
        <ListEmptyState
          title="Could not load requirements"
          message={(query.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : items.length === 0 ? (
        <ListEmptyState
          title="No requirements"
          message="No requirement templates apply to this student for the current term."
          icon="checkbox-outline"
        />
      ) : (
        <View>
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }}>
            {collected} of {items.length} requirements complete
          </Text>
          {items.map((item) => (
            <View
              key={item.template_id}
              style={[
                elevation[1],
                {
                  borderWidth: StyleSheet.hairlineWidth,
                  borderColor: palette.borderSubtle,
                  backgroundColor: palette.surfaceRaised,
                  borderRadius: radius.card,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <View style={styles.rowTop}>
                <Text
                  style={{
                    flex: 1,
                    color: palette.textPrimary,
                    fontWeight: '700',
                    fontSize: typography.body.fontSize,
                  }}
                >
                  {item.name}
                </Text>
                <StatusBadge
                  label={capitalizeStatus(item.status)}
                  tone={STATUS_TONES[item.status?.toLowerCase() ?? ''] ?? 'info'}
                  compact
                />
              </View>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {item.quantity_collected}/{item.quantity_required} {item.unit ?? ''}
                {item.brand ? ` · ${item.brand}` : ''}
              </Text>
              {item.notes ? (
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {item.notes}
                </Text>
              ) : null}
            </View>
          ))}
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.sm }}>
            Receiving items is done by teachers or on the web portal.
          </Text>
        </View>
      )}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  rowTop: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
