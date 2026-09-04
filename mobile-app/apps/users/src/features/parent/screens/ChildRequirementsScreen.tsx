import { useParentStudentRequirements } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ListRowCard,
  ScreenContainer,
  SkeletonListRows,
  SurfaceCard,
  useTheme,
  type SemanticTone,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Text } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatShortDate } from '../utils/format';

type Route = RouteProp<ParentStackParamList, 'ChildRequirements'>;

function statusTone(status: string): SemanticTone {
  const s = status.toLowerCase();
  if (s === 'complete' || s === 'collected' || s === 'fully_received') return 'success';
  if (s === 'partial' || s === 'partially_received') return 'warning';
  return 'info';
}

function statusLabel(status: string, outstanding: number): string {
  const s = status.toLowerCase();
  if (s === 'complete' || outstanding <= 0) return 'Brought';
  if (s === 'partial') return 'Partly brought';
  return 'Still needed';
}

export const ChildRequirementsScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<Route>();
  const { palette, spacing, typography } = useTheme();
  const studentId = route.params.studentId;
  const query = useParentStudentRequirements(studentId, { enabled: studentId > 0 });

  const items = query.data?.items ?? [];
  const summary = query.data?.summary;
  const broughtItems = useMemo(
    () => items.filter((i) => (i.quantity_collected ?? 0) > 0),
    [items],
  );
  const outstandingItems = useMemo(
    () =>
      items.filter((i) => {
        const outstanding =
          i.quantity_outstanding ?? Math.max(0, i.quantity_required - i.quantity_collected);
        return outstanding > 0;
      }),
    [items],
  );

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Requirements"
        subtitle={
          [
            query.data?.student.full_name,
            query.data?.current_term?.name,
            'What was brought vs still needed',
          ]
            .filter(Boolean)
            .join(' · ') || undefined
        }
        onBack={() => navigation.goBack()}
      />

      {query.isLoading ? (
        <SkeletonListRows count={4} />
      ) : query.isError ? (
        <EmptyState
          title="Could not load requirements"
          message={query.error instanceof Error ? query.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : items.length === 0 ? (
        <EmptyState
          title="No requirements listed"
          message="When the school sets class requirements for this term, they will appear here."
          icon="clipboard-outline"
        />
      ) : (
        <>
          {summary ? (
            <SurfaceCard accent="info" style={{ marginBottom: spacing.md }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>This term</Text>
              <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
                {summary.complete} complete · {summary.brought} with items brought ·{' '}
                {summary.outstanding} still outstanding
              </Text>
            </SurfaceCard>
          ) : null}

          <Text
            style={{
              color: palette.textSecondary,
              fontWeight: '700',
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
              textTransform: 'uppercase',
              letterSpacing: 0.4,
            }}
          >
            Brought
          </Text>
          {broughtItems.length === 0 ? (
            <Text style={{ color: palette.textMuted, marginBottom: spacing.md }}>
              Nothing recorded as brought yet for this term.
            </Text>
          ) : (
            broughtItems.map((item) => {
              const outstanding =
                item.quantity_outstanding ??
                Math.max(0, item.quantity_required - item.quantity_collected);
              const unit = item.unit ? ` ${item.unit}` : '';
              return (
                <ListRowCard
                  key={`brought-${item.template_id}`}
                  title={item.name}
                  subtitle={
                    item.brand
                      ? `${item.brand}${item.is_verification_only ? ' · learner keeps this' : ''}`
                      : item.is_verification_only
                        ? 'Learner keeps this'
                        : undefined
                  }
                  meta={[
                    `Brought ${item.quantity_collected}${unit} of ${item.quantity_required}${unit}`,
                    outstanding > 0 ? `${outstanding}${unit} still needed` : null,
                    item.last_received_at ? `Last ${formatShortDate(item.last_received_at)}` : null,
                  ]
                    .filter(Boolean)
                    .join(' · ')}
                  icon="checkmark-circle-outline"
                  glyph="receipt"
                  accent={outstanding > 0 ? 'warning' : 'success'}
                  badge={statusLabel(item.status, outstanding)}
                  badgeTone={statusTone(item.status)}
                />
              );
            })
          )}

          <Text
            style={{
              color: palette.textSecondary,
              fontWeight: '700',
              fontSize: typography.caption.fontSize,
              marginTop: spacing.md,
              marginBottom: spacing.xs,
              textTransform: 'uppercase',
              letterSpacing: 0.4,
            }}
          >
            Still needed
          </Text>
          {outstandingItems.length === 0 ? (
            <Text style={{ color: palette.textMuted }}>All listed requirements have been brought.</Text>
          ) : (
            outstandingItems.map((item) => {
              const outstanding =
                item.quantity_outstanding ??
                Math.max(0, item.quantity_required - item.quantity_collected);
              const unit = item.unit ? ` ${item.unit}` : '';
              const alreadyBrought = (item.quantity_collected ?? 0) > 0;
              return (
                <ListRowCard
                  key={`need-${item.template_id}`}
                  title={item.name}
                  subtitle={item.brand ?? undefined}
                  meta={
                    alreadyBrought
                      ? `Need ${outstanding}${unit} more (already brought ${item.quantity_collected}${unit})`
                      : `Need ${outstanding}${unit} of ${item.quantity_required}${unit}`
                  }
                  icon="clipboard-outline"
                  glyph="activities"
                  accent="warning"
                  badge={alreadyBrought ? 'Partly brought' : 'Still needed'}
                  badgeTone={alreadyBrought ? 'warning' : 'info'}
                />
              );
            })
          )}
        </>
      )}
    </ScreenContainer>
  );
};
