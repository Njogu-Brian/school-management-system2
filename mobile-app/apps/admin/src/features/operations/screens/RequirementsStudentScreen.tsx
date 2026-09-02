import { useCan, useCollectStudentRequirement, useStudentRequirements } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { capitalizeStatus } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'RequirementsStudent'>;

const STATUS_TONES: Record<string, 'success' | 'warning' | 'danger' | 'info'> = {
  complete: 'success',
  collected: 'success',
  partial: 'warning',
  pending: 'danger',
};

function remainingFor(item: { quantity_required: number; quantity_collected: number }): number {
  return Math.max(0, item.quantity_required - item.quantity_collected);
}

export const RequirementsStudentScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('operations.view');
  const { palette, spacing, typography, radius, elevation, colors } = useTheme();
  const query = useStudentRequirements(route.params.studentId, { enabled: canView });
  const collectMutation = useCollectStudentRequirement();
  const [qtyByTemplate, setQtyByTemplate] = useState<Record<number, string>>({});
  const [collectingId, setCollectingId] = useState<number | null>(null);

  const data = query.data;
  const items = data?.items ?? [];

  useEffect(() => {
    setQtyByTemplate((prev) => {
      const next = { ...prev };
      for (const item of items) {
        if (next[item.template_id] === undefined) {
          const remaining = remainingFor(item);
          next[item.template_id] = remaining > 0 ? String(remaining) : '';
        }
      }
      return next;
    });
  }, [items]);

  const submit = async (templateId: number) => {
    const quantity = Number(qtyByTemplate[templateId] ?? '');
    if (!Number.isFinite(quantity) || quantity <= 0) {
      showError('Quantity needed', 'Enter how many items you received.');
      return;
    }
    setCollectingId(templateId);
    try {
      await collectMutation.mutateAsync({
        student_id: route.params.studentId,
        template_id: templateId,
        quantity_received: quantity,
      });
      setQtyByTemplate((prev) => ({ ...prev, [templateId]: '' }));
      showSuccess('Recorded', 'Requirement collection saved.');
    } catch (err) {
      showError('Could not collect', err instanceof Error ? err.message : 'Try again.');
    } finally {
      setCollectingId(null);
    }
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const collected = items.filter(
    (i) => i.quantity_collected >= i.quantity_required && i.quantity_required > 0,
  ).length;

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
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
          {items.map((item) => {
            const remaining = remainingFor(item);
            const busy = collectingId === item.template_id;
            return (
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
                  {remaining > 0 ? ` · ${remaining} remaining` : ''}
                  {item.is_verification_only ? ' · Verify only (learner keeps)' : item.adds_to_inventory ? ' · Collect into school stock' : ''}
                </Text>
                {item.notes ? (
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    {item.notes}
                  </Text>
                ) : null}
                {remaining > 0 ? (
                  <View style={{ marginTop: spacing.sm, gap: spacing.sm }}>
                    <TextField
                      label={`Quantity received${item.unit ? ` (${item.unit})` : ''}`}
                      value={qtyByTemplate[item.template_id] ?? ''}
                      onChangeText={(value) =>
                        setQtyByTemplate((prev) => ({ ...prev, [item.template_id]: value }))
                      }
                      keyboardType="decimal-pad"
                      placeholder={String(remaining)}
                    />
                    <Button
                      label={item.is_verification_only ? 'Verify learner has this' : 'Record received'}
                      onPress={() => void submit(item.template_id)}
                      loading={busy}
                      disabled={collectMutation.isPending}
                    />
                  </View>
                ) : (
                  <Text
                    style={{
                      color: colors.success,
                      fontSize: typography.caption.fontSize,
                      fontWeight: '600',
                      marginTop: spacing.sm,
                    }}
                  >
                    Collection complete
                  </Text>
                )}
              </View>
            );
          })}
        </View>
      )}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  rowTop: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
