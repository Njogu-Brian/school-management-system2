import { useCollectStudentRequirement, useStudentRequirements } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TextField,
  useTheme,
  type SemanticTone,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'RequirementDetail'>;

function remainingFor(item: { quantity_required: number; quantity_collected: number }): number {
  return Math.max(0, item.quantity_required - item.quantity_collected);
}

function statusTone(status: string): SemanticTone {
  const s = status.toLowerCase();
  if (s === 'collected' || s === 'complete' || s === 'fully_received') return 'success';
  if (s === 'partial' || s === 'partially_received') return 'warning';
  return 'info';
}

export const RequirementDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { studentId } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const detailQuery = useStudentRequirements(studentId);
  const collectMutation = useCollectStudentRequirement();
  const [qtyByTemplate, setQtyByTemplate] = useState<Record<number, string>>({});
  const [collectingId, setCollectingId] = useState<number | null>(null);

  const student = detailQuery.data?.student;
  const items = detailQuery.data?.items ?? [];

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
    const raw = qtyByTemplate[templateId] ?? '';
    const quantity = Number(raw);
    if (!Number.isFinite(quantity) || quantity <= 0) {
      showError('Quantity needed', 'Enter how many items you received.');
      return;
    }
    setCollectingId(templateId);
    try {
      await collectMutation.mutateAsync({
        student_id: studentId,
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

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl * 3, flexGrow: 1 }}
        refreshControl={
          <RefreshControl
            refreshing={detailQuery.isRefetching}
            onRefresh={() => void detailQuery.refetch()}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader
          title={student?.full_name ?? 'Requirements'}
          subtitle={
            [student?.admission_number, student?.class_name, detailQuery.data?.current_term?.name]
              .filter(Boolean)
              .join(' · ') || `Student #${studentId}`
          }
          onBack={() => navigation.goBack()}
        />

        {detailQuery.isLoading && !detailQuery.data ? (
          <SkeletonListRows variant="compact" count={4} />
        ) : detailQuery.isError ? (
          <EmptyState
            title="Could not load requirements"
            message={(detailQuery.error as Error)?.message ?? 'Something went wrong.'}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void detailQuery.refetch()}
          />
        ) : items.length === 0 ? (
          <EmptyState
            title="No templates"
            message="No requirement templates for this student."
            icon="clipboard-outline"
          />
        ) : (
          items.map((item) => {
            const remaining = remainingFor(item);
            const busy = collectingId === item.template_id;
            return (
              <View
                key={item.template_id}
                style={[
                  styles.card,
                  {
                    backgroundColor: palette.surface,
                    borderColor: palette.border,
                    borderRadius: radius.lg,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                  },
                ]}
              >
                <View style={styles.header}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>
                    {item.name}
                  </Text>
                  <StatusBadge label={item.status.replace(/_/g, ' ')} tone={statusTone(item.status)} compact />
                </View>
                {item.brand ? (
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    {item.brand}
                  </Text>
                ) : null}
                {item.notes ? (
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    {item.notes}
                  </Text>
                ) : null}
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 6 }}>
                  {`${item.quantity_collected}/${item.quantity_required}${item.unit ? ` ${item.unit}` : ''} ${item.is_verification_only ? 'verified' : 'collected'}`}
                  {remaining > 0 ? ` · ${remaining} remaining` : ''}
                  {item.is_verification_only ? ' · learner keeps this' : ''}
                </Text>
                {remaining > 0 ? (
                  <View style={{ marginTop: spacing.sm, gap: spacing.sm }}>
                    <TextField
                      label={`Quantity ${item.is_verification_only ? 'verified' : 'received'}${item.unit ? ` (${item.unit})` : ''}`}
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
          })
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  header: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
