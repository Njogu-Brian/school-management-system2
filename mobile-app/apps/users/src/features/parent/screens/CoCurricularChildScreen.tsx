import {
  useCancelCoCurricularRequest,
  useParentCoCurricular,
  useRequestCoCurricularChange,
  type CoCurricularOffer,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  StatusBadge,
  SurfaceCard,
  useTheme,
  type Soft3DGlyphKey,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';
import { formatKes, formatShortDate } from '../utils/format';

type Route = RouteProp<ParentStackParamList, 'CoCurricularChild'>;

function glyphFor(icon: string): Soft3DGlyphKey {
  if (
    icon === 'ballet' ||
    icon === 'skating' ||
    icon === 'music' ||
    icon === 'yogurt' ||
    icon === 'swimming' ||
    icon === 'activities'
  ) {
    return icon;
  }
  return 'activities';
}

function ActivityCard({
  offer,
  childName,
  busy,
  onJoin,
  onLeave,
  onCancel,
}: {
  offer: CoCurricularOffer;
  childName: string;
  busy: boolean;
  onJoin: () => void;
  onLeave: () => void;
  onCancel: () => void;
}) {
  const { palette, spacing, typography, colors, radius } = useTheme();
  const cost = offer.enrolled && offer.billed_amount != null ? offer.billed_amount : offer.amount;
  const pending = offer.pending_request;

  return (
    <SurfaceCard accent={offer.enrolled ? 'success' : pending ? 'warning' : 'info'}>
      <View style={{ flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' }}>
        <Soft3DIcon glyph={glyphFor(offer.icon)} size={56} />
        <View style={{ flex: 1 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '800',
                fontSize: typography.titleSmall.fontSize,
                flex: 1,
              }}
            >
              {offer.name}
            </Text>
            {offer.enrolled ? <StatusBadge label="Enrolled" tone="success" compact /> : null}
          </View>
          <Text
            style={{
              color: colors.primary,
              fontWeight: '800',
              fontSize: 20,
              marginTop: 4,
            }}
          >
            {formatKes(cost)}
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, fontWeight: '600' }}>
              {' '}
              / term
            </Text>
          </Text>
          {offer.enrolled ? (
            <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
              {childName} is doing {offer.name}
            </Text>
          ) : (
            <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
              Available to join this term
            </Text>
          )}
        </View>
      </View>

      {offer.enrolled && offer.attendance ? (
        <View
          style={{
            marginTop: spacing.md,
            backgroundColor: palette.surfaceMuted,
            borderRadius: radius.md,
            padding: spacing.sm,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: 6 }}>
            Attendance marked
          </Text>
          {offer.attendance.present_count === 0 ? (
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
              No sessions marked yet this term.
            </Text>
          ) : (
            <>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {offer.attendance.present_count} session
                {offer.attendance.present_count === 1 ? '' : 's'} present
                {offer.attendance.last_date
                  ? ` · last ${formatShortDate(offer.attendance.last_date)}`
                  : ''}
              </Text>
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 8 }}>
                {offer.attendance.recent.map((row, idx) => (
                  <View
                    key={`${row.date}-${idx}`}
                    style={{
                      backgroundColor: '#D1FAE5',
                      borderRadius: 8,
                      paddingHorizontal: 8,
                      paddingVertical: 4,
                    }}
                  >
                    <Text style={{ color: '#047857', fontSize: 11, fontWeight: '700' }}>
                      {row.date ? formatShortDate(row.date) : 'Present'}
                    </Text>
                  </View>
                ))}
              </View>
            </>
          )}
        </View>
      ) : null}

      {pending ? (
        <View style={{ marginTop: spacing.md }}>
          <StatusBadge
            label={pending.action === 'leave' ? 'Leave waiting' : 'Join waiting'}
            tone="warning"
          />
          <Text style={{ color: palette.textSecondary, marginTop: 6, fontSize: typography.caption.fontSize }}>
            The school office has been notified and will confirm this change.
          </Text>
          <Button
            label="Cancel request"
            variant="ghost"
            disabled={busy}
            onPress={onCancel}
            style={{ marginTop: spacing.sm }}
          />
        </View>
      ) : offer.enrolled ? (
        <Button
          label={`Stop ${offer.name}`}
          variant="outlined"
          disabled={busy}
          onPress={onLeave}
          style={{ marginTop: spacing.md }}
        />
      ) : (
        <Button
          label={`Join ${offer.name} · ${formatKes(cost)}`}
          disabled={busy}
          onPress={onJoin}
          style={{ marginTop: spacing.md }}
        />
      )}
    </SurfaceCard>
  );
}

export const CoCurricularChildScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { palette, spacing, typography, colors, radius } = useTheme();
  const studentId = route.params.studentId;
  const [period, setPeriod] = useState<{ year?: number; term?: number }>({});
  const query = useParentCoCurricular(studentId, period, { enabled: studentId > 0 });
  const requestChange = useRequestCoCurricularChange(studentId);
  const cancelReq = useCancelCoCurricularRequest(studentId);

  const data = query.data;
  const childName = data?.student.full_name ?? 'Your child';
  const selected = data?.selected_term;
  const busy = requestChange.isPending || cancelReq.isPending;

  const confirmMessage =
    data?.confirmation_message ??
    'The school office has been notified and will confirm this change.';

  const ask = (offer: CoCurricularOffer, action: 'join' | 'leave') => {
    if (!selected) return;
    const verb = action === 'leave' ? `stop ${offer.name}` : `join ${offer.name}`;
    confirmAction(
      action === 'leave' ? 'Leave activity' : 'Join activity',
      `Ask the school for ${childName} to ${verb} in ${selected.label}? Fees will only change after the office confirms.`,
      'Send request',
      () => {
        void requestChange
          .mutateAsync({
            votehead_id: offer.votehead_id,
            action,
            year: selected.year,
            term: selected.term,
          })
          .then(() => showSuccess('Request sent', confirmMessage))
          .catch((err) => showError('Could not send', err instanceof Error ? err.message : 'Try again.'));
      },
    );
  };

  const enrolledActivities = useMemo(
    () => (data?.activities ?? []).filter((a) => a.enrolled),
    [data?.activities],
  );
  const availableActivities = useMemo(
    () => (data?.activities ?? []).filter((a) => !a.enrolled),
    [data?.activities],
  );

  if (studentId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Co-curricular" onBack={() => navigation.goBack()} />
        <EmptyState title="Missing student" message="No child was selected." icon="alert-circle-outline" />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Co-curricular"
        subtitle={[childName, data?.student.class_name, data?.student.category_name]
          .filter(Boolean)
          .join(' · ')}
        onBack={() => navigation.goBack()}
      />

      {data ? (
        <FilterChipRow label="Term">
          <FilterChip
            label={data.current_term.label}
            active={!!selected?.is_current}
            onPress={() =>
              setPeriod({ year: data.current_term.year, term: data.current_term.term })
            }
          />
          <FilterChip
            label={`Upcoming · ${data.upcoming_term.label}`}
            active={!!selected?.is_upcoming}
            onPress={() =>
              setPeriod({ year: data.upcoming_term.year, term: data.upcoming_term.term })
            }
          />
        </FilterChipRow>
      ) : null}

      {query.isLoading ? (
        <SkeletonListRows count={4} />
      ) : query.isError ? (
        <EmptyState
          title="Could not load activities"
          message={query.error instanceof Error ? query.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : !data ? null : (
        <>
          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: '800',
              fontSize: typography.title.fontSize,
              marginBottom: spacing.sm,
              marginTop: spacing.sm,
            }}
          >
            Current activities
          </Text>
          {enrolledActivities.length === 0 ? (
            <SurfaceCard accent="info">
              <Text style={{ color: palette.textSecondary }}>
                {childName} is not enrolled in ballet, skating, music or similar programmes this term.
              </Text>
            </SurfaceCard>
          ) : (
            enrolledActivities.map((offer) => (
              <ActivityCard
                key={offer.votehead_id}
                offer={offer}
                childName={childName}
                busy={busy}
                onJoin={() => ask(offer, 'join')}
                onLeave={() => ask(offer, 'leave')}
                onCancel={() => {
                  if (!offer.pending_request) return;
                  void cancelReq
                    .mutateAsync(offer.pending_request.id)
                    .then(() => showSuccess('Cancelled', 'The waiting request was cancelled.'))
                    .catch((err) =>
                      showError('Could not cancel', err instanceof Error ? err.message : 'Try again.'),
                    );
                }}
              />
            ))
          )}

          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: '800',
              fontSize: typography.title.fontSize,
              marginBottom: spacing.sm,
              marginTop: spacing.sm,
            }}
          >
            Join an activity
          </Text>
          {availableActivities.length === 0 ? (
            <SurfaceCard>
              <Text style={{ color: palette.textSecondary }}>
                No other programmes are open for this class and category this term.
              </Text>
            </SurfaceCard>
          ) : (
            availableActivities.map((offer) => (
              <ActivityCard
                key={offer.votehead_id}
                offer={offer}
                childName={childName}
                busy={busy}
                onJoin={() => ask(offer, 'join')}
                onLeave={() => ask(offer, 'leave')}
                onCancel={() => {
                  if (!offer.pending_request) return;
                  void cancelReq
                    .mutateAsync(offer.pending_request.id)
                    .then(() => showSuccess('Cancelled', 'The waiting request was cancelled.'))
                    .catch((err) =>
                      showError('Could not cancel', err instanceof Error ? err.message : 'Try again.'),
                    );
                }}
              />
            ))
          )}

          <View
            style={{
              marginTop: spacing.sm,
              marginBottom: spacing.sm,
              padding: spacing.md,
              borderRadius: radius.lg,
              backgroundColor: '#FEF3C7',
            }}
          >
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
              <Soft3DIcon glyph="yogurt" size={44} />
              <Text style={{ color: '#92400E', fontWeight: '800', fontSize: typography.title.fontSize, flex: 1 }}>
                Yoghurt
              </Text>
            </View>
            <Text style={{ color: '#78350F', marginTop: 6, fontSize: typography.caption.fontSize }}>
              Separate from clubs — billed like an activity for this class and category.
            </Text>
          </View>
          {(data.yogurt ?? []).length === 0 ? (
            <SurfaceCard accent="warning">
              <Text style={{ color: palette.textSecondary }}>Yoghurt is not on the fee list for this term.</Text>
            </SurfaceCard>
          ) : (
            data.yogurt.map((offer) => (
              <ActivityCard
                key={offer.votehead_id}
                offer={offer}
                childName={childName}
                busy={busy}
                onJoin={() => ask(offer, 'join')}
                onLeave={() => ask(offer, 'leave')}
                onCancel={() => {
                  if (!offer.pending_request) return;
                  void cancelReq
                    .mutateAsync(offer.pending_request.id)
                    .then(() => showSuccess('Cancelled', 'The waiting request was cancelled.'))
                    .catch((err) =>
                      showError('Could not cancel', err instanceof Error ? err.message : 'Try again.'),
                    );
                }}
              />
            ))
          )}
        </>
      )}
    </ScreenContainer>
  );
};
