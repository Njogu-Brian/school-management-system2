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
  ScreenContainer,
  SkeletonListRows,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import React, { useMemo, useState } from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { goBackInStack } from '../../../navigation/navigateToTab';
import { showError, showSuccess } from '../../shared/utils/feedback';

type TransportMode = 'vehicle' | 'trip' | 'own_means';
type ChangeDuration = 'temporary' | 'permanent';

function OptionPicker({
  label,
  options,
  value,
  onChange,
}: {
  label: string;
  options: Array<{ id: number; label: string }>;
  value: number | null;
  onChange: (id: number | null) => void;
}) {
  const { palette, spacing, typography, radius } = useTheme();
  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text style={{ color: palette.textSecondary, fontWeight: '600', marginBottom: spacing.xs }}>{label}</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false}>
        <View style={{ flexDirection: 'row', gap: spacing.sm }}>
          {options.map((opt) => {
            const active = value === opt.id;
            return (
              <Pressable
                key={opt.id}
                onPress={() => onChange(active ? null : opt.id)}
                style={{
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.sm,
                  borderRadius: radius.full,
                  borderWidth: 1,
                  borderColor: active ? palette.primary : palette.border,
                  backgroundColor: active ? palette.primaryMuted : palette.surface,
                  maxWidth: 220,
                }}
              >
                <Text
                  numberOfLines={2}
                  style={{
                    color: active ? palette.primary : palette.textPrimary,
                    fontWeight: active ? '700' : '500',
                    fontSize: typography.caption.fontSize,
                  }}
                >
                  {opt.label}
                </Text>
              </Pressable>
            );
          })}
        </View>
      </ScrollView>
    </View>
  );
}

export const TransportScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'Transport'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

  const today = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const [mode, setMode] = useState<TransportMode>('own_means');
  const [changeDuration, setChangeDuration] = useState<ChangeDuration>('temporary');
  const [startDate, setStartDate] = useState(today);
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');
  const [tripId, setTripId] = useState<number | null>(null);
  const [dropOffPointId, setDropOffPointId] = useState<number | null>(null);
  const [vehicleId, setVehicleId] = useState<number | null>(null);
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
    if (mode === 'trip' && !tripId) {
      showError('Trip required', 'Select the trip you want for this child.');
      return;
    }
    if (mode === 'vehicle' && !vehicleId) {
      showError('Vehicle required', 'Select a vehicle for this request.');
      return;
    }
    setSubmitting(true);
    try {
      const res = await transportSpecialApi.create({
        student_id: studentId,
        transport_mode: mode,
        trip_id: mode === 'trip' ? tripId : null,
        vehicle_id: mode === 'vehicle' ? vehicleId : null,
        drop_off_point_id: mode === 'own_means' ? null : dropOffPointId,
        start_date: startDate.trim(),
        end_date: changeDuration === 'temporary' ? endDate.trim() : null,
        reason: reason.trim(),
        activate: false,
      });
      if (!res.success) throw new Error(res.message || 'Request failed.');
      showSuccess('Request submitted', 'School admin will review before it becomes active.');
      setReason('');
      setEndDate('');
      setTripId(null);
      setVehicleId(null);
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

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Transport"
        subtitle={d?.fullName ?? undefined}
        onBack={() => goBackInStack(navigation, 'ChildrenList')}
      />

      {studentId <= 0 ? (
        <EmptyState title="Missing student" message="Select a child first." icon="bus-outline" />
      ) : (
        <>
          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
              Current assignment
            </Text>
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
                {!morning?.tripName && !evening?.tripName ? (
                  <>
                    <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
                      Trip: {d?.tripName ?? (d?.tripId ? `#${d.tripId}` : '—')}
                      {d?.tripVehicle ? ` · ${d.tripVehicle}` : ''}
                    </Text>
                    <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
                      Drop-off: {d?.dropOffPointName ?? d?.dropOffPointOther ?? (d?.dropOffPointId ? `#${d.dropOffPointId}` : '—')}
                    </Text>
                  </>
                ) : null}
              </>
            )}
          </View>

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
              <View
                key={row.id}
                style={{
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                  {row.transport_mode.replace('_', ' ')} · {row.status}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {[row.trip_name, row.vehicle_number, row.drop_off_point].filter(Boolean).join(' · ') || 'Details pending'}
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
              </View>
            ))
          )}

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm, marginTop: spacing.sm }}>
            Request a change
          </Text>
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
            Choose the new arrangement. School admin must approve before it becomes active.
          </Text>

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

          <FilterChipRow label="Mode">
            {(
              [
                { id: 'own_means', label: 'Own means' },
                { id: 'trip', label: 'School trip' },
                { id: 'vehicle', label: 'Vehicle' },
              ] as const
            ).map((opt) => (
              <FilterChip
                key={opt.id}
                label={opt.label}
                active={mode === opt.id}
                onPress={() => {
                  setMode(opt.id);
                  setTripId(null);
                  setVehicleId(null);
                }}
              />
            ))}
          </FilterChipRow>

          {mode === 'trip' ? (
            <OptionPicker
              label="Select trip"
              options={(optionsQuery.data?.trips ?? []).map((t) => ({ id: t.id, label: t.label }))}
              value={tripId}
              onChange={setTripId}
            />
          ) : null}
          {mode === 'vehicle' ? (
            <OptionPicker
              label="Select vehicle"
              options={(optionsQuery.data?.vehicles ?? []).map((v) => ({ id: v.id, label: v.label }))}
              value={vehicleId}
              onChange={setVehicleId}
            />
          ) : null}
          {mode !== 'own_means' ? (
            <OptionPicker
              label="Drop-off point (optional)"
              options={(optionsQuery.data?.drop_off_points ?? []).map((p) => ({ id: p.id, label: p.label }))}
              value={dropOffPointId}
              onChange={setDropOffPointId}
            />
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
        </>
      )}
    </ScreenContainer>
  );
};
