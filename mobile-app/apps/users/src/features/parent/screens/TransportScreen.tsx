import { transportSpecialApi, useStudentDetail } from '@erp/core';
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
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type TransportMode = 'vehicle' | 'trip' | 'own_means';
type ChangeDuration = 'temporary' | 'permanent';

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
  const [submitting, setSubmitting] = useState(false);

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
    setSubmitting(true);
    try {
      const res = await transportSpecialApi.create({
        student_id: studentId,
        transport_mode: mode,
        start_date: startDate.trim(),
        end_date: changeDuration === 'temporary' ? endDate.trim() : null,
        reason: reason.trim(),
        activate: false,
      });
      if (!res.success) throw new Error(res.message || 'Request failed.');
      showSuccess('Request submitted', 'School admin will review before it becomes active.');
      setReason('');
      setEndDate('');
      void assignmentsQuery.refetch();
    } catch (err) {
      showError('Request failed', err instanceof Error ? err.message : 'Could not submit change request.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Transport"
        subtitle={detail.data?.fullName ?? undefined}
        onBack={() => navigation.goBack()}
      />

      {studentId <= 0 ? (
        <EmptyState title="Missing student" message="Select a child first." icon="alert-circle-outline" />
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
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              Trip ID: {detail.data?.tripId ?? '—'}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
              Drop-off point ID: {detail.data?.dropOffPointId ?? '—'}
            </Text>
            {detail.data?.dropOffPointOther ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                Other drop-off: {detail.data.dropOffPointOther}
              </Text>
            ) : null}
            {!detail.data?.tripId && !detail.data?.dropOffPointId && !detail.data?.dropOffPointOther ? (
              <Text style={{ color: palette.textMuted, marginTop: spacing.sm }}>
                No transport details on file for this child.
              </Text>
            ) : null}
          </View>

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
            Special assignments
          </Text>
          {assignmentsQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={2} />
          ) : assignmentsQuery.isError ? (
            <Text style={{ color: palette.textMuted, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
              Could not load past requests.
            </Text>
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
            Requests are submitted inactive until admin approval.
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

          {changeDuration === 'permanent' ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginBottom: spacing.sm,
                fontStyle: 'italic',
              }}
            >
              Permanent changes require school approval and do not use an end date.
            </Text>
          ) : (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
              Temporary changes must include an end date.
            </Text>
          )}

          <FilterChipRow label="Mode">
            {(
              [
                { id: 'own_means', label: 'Own means' },
                { id: 'vehicle', label: 'Vehicle' },
                { id: 'trip', label: 'Trip' },
              ] as const
            ).map((opt) => (
              <FilterChip
                key={opt.id}
                label={opt.label}
                active={mode === opt.id}
                onPress={() => setMode(opt.id)}
              />
            ))}
          </FilterChipRow>

          <TextField label="Start date (YYYY-MM-DD)" value={startDate} onChangeText={setStartDate} />
          {changeDuration === 'temporary' ? (
            <TextField
              label="End date (required)"
              value={endDate}
              onChangeText={setEndDate}
              placeholder="YYYY-MM-DD"
            />
          ) : null}
          <TextField
            label="Reason"
            value={reason}
            onChangeText={setReason}
            placeholder="Why do you need this change?"
            multiline
          />

          <Button
            label="Submit request"
            loading={submitting}
            onPress={() => void submit()}
            style={{ marginTop: spacing.md }}
          />
        </>
      )}
    </ScreenContainer>
  );
};
