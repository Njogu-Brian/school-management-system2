import {
  parentTransportApi,
  transportSpecialApi,
  useStudentDetail,
  type ParentTransportOptions,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  OptionSelectField,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  SurfaceCard,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import React, { useMemo, useState } from 'react';
import { RefreshControl, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { goBackInStack } from '../../../navigation/navigateToTab';
import { showError, showSuccess } from '../../shared/utils/feedback';

type ChangeDuration = 'temporary' | 'permanent';
type Leg = 'morning' | 'evening' | 'both';
type ChangeKind = 'drop_off' | 'own_means';

export const TransportScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'Transport'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

  const today = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const [changeDuration, setChangeDuration] = useState<ChangeDuration>('temporary');
  const [leg, setLeg] = useState<Leg>('evening');
  const [changeKind, setChangeKind] = useState<ChangeKind>('drop_off');
  const [startDate, setStartDate] = useState(today);
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');
  const [dropOffPointId, setDropOffPointId] = useState<number | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const optionsQuery = useQuery({
    queryKey: ['parent-transport-options'],
    queryFn: async () => {
      const res = await parentTransportApi.options();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load transport options.');
      return res.data as ParentTransportOptions;
    },
    staleTime: 5 * 60_000,
  });

  const assignmentsQuery = useQuery({
    queryKey: ['transport-special', studentId],
    queryFn: async () => {
      const res = await transportSpecialApi.list({ student_id: studentId, per_page: 20 });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load assignments.');
      return res.data.data ?? [];
    },
    enabled: studentId > 0,
    staleTime: 60_000,
  });

  const dropOffOptions = useMemo(() => {
    const points = optionsQuery.data?.drop_off_points ?? [];
    return [...points]
      .map((p) => ({ id: p.id, label: p.label }))
      .sort((a, b) => a.label.localeCompare(b.label, undefined, { sensitivity: 'base' }));
  }, [optionsQuery.data]);

  const dropOffLabels = useMemo(() => dropOffOptions.map((o) => o.label), [dropOffOptions]);
  const selectedDropOffLabel =
    dropOffOptions.find((o) => o.id === dropOffPointId)?.label ?? '';

  const resolveTripIdForLeg = (): number | null => {
    const d = detail.data;
    if (leg === 'morning') return d?.transportMorning?.tripId ?? d?.tripId ?? null;
    if (leg === 'evening') return d?.transportEvening?.tripId ?? d?.tripId ?? null;
    return d?.transportMorning?.tripId ?? d?.transportEvening?.tripId ?? d?.tripId ?? null;
  };

  const submit = async () => {
    if (!reason.trim()) {
      showError('Reason required', 'Tell the school why you need a transport change.');
      return;
    }
    if (!startDate.trim()) {
      showError('Start date required', 'Use YYYY-MM-DD.');
      return;
    }
    if (changeDuration === 'temporary' && !endDate.trim()) {
      showError('End date required', 'Temporary changes need an end date (YYYY-MM-DD).');
      return;
    }
    if (changeKind === 'drop_off' && !dropOffPointId) {
      showError('Drop-off required', 'Select a drop-off point.');
      return;
    }

    const tripId = changeKind === 'drop_off' ? resolveTripIdForLeg() : null;
    if (changeKind === 'drop_off' && !tripId) {
      showError(
        'No current trip',
        'This child has no trip assigned for that time of day. Choose Own means, or ask the school to set transport first.',
      );
      return;
    }

    const legLabel = leg === 'both' ? 'Morning & evening' : leg === 'morning' ? 'Morning' : 'Evening';
    const reasonWithLeg = `[${legLabel}] ${reason.trim()}`;

    setSubmitting(true);
    try {
      const res = await transportSpecialApi.create({
        student_id: studentId,
        transport_mode: changeKind === 'own_means' ? 'own_means' : 'trip',
        trip_id: changeKind === 'drop_off' ? tripId : null,
        vehicle_id: null,
        drop_off_point_id: changeKind === 'drop_off' ? dropOffPointId : null,
        start_date: startDate.trim(),
        end_date: changeDuration === 'temporary' ? endDate.trim() : null,
        reason: reasonWithLeg,
        activate: false,
      });
      if (!res.success) throw new Error(res.message || 'Request failed.');
      showSuccess('Request submitted', 'School admin will review before it becomes active.');
      setReason('');
      setEndDate('');
      setDropOffPointId(null);
      void assignmentsQuery.refetch();
    } catch (err) {
      showError('Request failed', err instanceof Error ? err.message : 'Could not submit change request.');
    } finally {
      setSubmitting(false);
    }
  };

  const d = detail.data;
  const morning = d?.transportMorning;
  const evening = d?.transportEvening;
  const refreshing = detail.isRefetching || optionsQuery.isRefetching || assignmentsQuery.isRefetching;

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      scrollProps={{
        refreshControl: (
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => {
              void detail.refetch();
              void optionsQuery.refetch();
              void assignmentsQuery.refetch();
            }}
          />
        ),
      }}
    >
      <AcademicScreenHeader
        title="Transport"
        subtitle={d?.fullName ?? undefined}
        onBack={() => goBackInStack(navigation, 'ChildrenList')}
      />

      {studentId <= 0 ? (
        <EmptyState title="Missing student" message="Select a child first." icon="bus-outline" />
      ) : (
        <>
          <SurfaceCard accent="info">
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.sm }}>
              <Soft3DIcon name="bus-outline" glyph="bus" size={44} />
              <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>
                Current assignment
              </Text>
            </View>
            {detail.isLoading ? (
              <SkeletonListRows variant="compact" count={2} />
            ) : (
              <>
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                  {d?.transportSummary || 'No transport assigned'}
                </Text>
                {morning?.tripName ? (
                  <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
                    Morning · {[morning.tripName, morning.vehicle, morning.dropOffPoint].filter(Boolean).join(' · ')}
                  </Text>
                ) : null}
                {evening?.tripName ? (
                  <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
                    Evening · {[evening.tripName, evening.vehicle, evening.dropOffPoint].filter(Boolean).join(' · ')}
                  </Text>
                ) : null}
              </>
            )}
          </SurfaceCard>

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
            Change requests
          </Text>
          {assignmentsQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={2} />
          ) : (assignmentsQuery.data ?? []).length === 0 ? (
            <Text style={{ color: palette.textMuted, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
              No special transport requests on file.
            </Text>
          ) : (
            (assignmentsQuery.data ?? []).map((row) => (
              <SurfaceCard key={row.id} accent={row.status === 'pending' ? 'warning' : 'success'}>
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                  {row.transport_mode.replace('_', ' ')} · {row.status}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {[row.trip_name, row.vehicle_number, row.drop_off_point].filter(Boolean).join(' · ') ||
                    'Details pending'}
                </Text>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {row.start_date}
                  {row.end_date ? ` → ${row.end_date}` : ' · permanent'}
                </Text>
                {row.reason ? (
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    {row.reason}
                  </Text>
                ) : null}
              </SurfaceCard>
            ))
          )}

          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginTop: spacing.sm,
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
              Request a change
            </Text>
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
              Choose morning or evening, then pick a new drop-off point. School admin must approve before it becomes
              active.
            </Text>

            <FilterChipRow label="Applies to">
              {(
                [
                  { id: 'morning', label: 'Morning' },
                  { id: 'evening', label: 'Evening' },
                  { id: 'both', label: 'Both' },
                ] as const
              ).map((opt) => (
                <FilterChip
                  key={opt.id}
                  label={opt.label}
                  active={leg === opt.id}
                  onPress={() => setLeg(opt.id)}
                />
              ))}
            </FilterChipRow>

            <FilterChipRow label="Duration">
              {(
                [
                  { id: 'temporary', label: 'Temporary' },
                  { id: 'permanent', label: 'Permanent' },
                ] as const
              ).map((opt) => (
                <FilterChip
                  key={opt.id}
                  label={opt.label}
                  active={changeDuration === opt.id}
                  onPress={() => setChangeDuration(opt.id)}
                />
              ))}
            </FilterChipRow>

            <FilterChipRow label="Change type">
              <FilterChip
                label="New drop-off"
                active={changeKind === 'drop_off'}
                onPress={() => setChangeKind('drop_off')}
              />
              <FilterChip
                label="Own means"
                active={changeKind === 'own_means'}
                onPress={() => {
                  setChangeKind('own_means');
                  setDropOffPointId(null);
                }}
              />
            </FilterChipRow>

            {changeKind === 'drop_off' ? (
              optionsQuery.isLoading ? (
                <SkeletonListRows variant="compact" count={1} />
              ) : (
                <OptionSelectField
                  label="Drop-off point"
                  value={selectedDropOffLabel}
                  options={dropOffLabels}
                  searchable
                  required
                  placeholder="Select drop-off (A–Z)"
                  onChange={(label) => {
                    const match = dropOffOptions.find((o) => o.label === label);
                    setDropOffPointId(match?.id ?? null);
                  }}
                />
              )
            ) : null}

            <TextField label="Start date (YYYY-MM-DD)" value={startDate} onChangeText={setStartDate} />
            {changeDuration === 'temporary' ? (
              <TextField label="End date (YYYY-MM-DD)" value={endDate} onChangeText={setEndDate} />
            ) : null}
            <TextField label="Reason" value={reason} onChangeText={setReason} multiline />

            <Button
              label={submitting ? 'Submitting…' : 'Submit change request'}
              onPress={() => void submit()}
              disabled={submitting}
              style={{ marginTop: spacing.sm }}
            />
          </View>
        </>
      )}
    </ScreenContainer>
  );
};
