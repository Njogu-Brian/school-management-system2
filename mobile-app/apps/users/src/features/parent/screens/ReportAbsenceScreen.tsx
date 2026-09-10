import {
  useAttendanceReasonCodes,
  useReportParentAbsence,
  useStudentDetail,
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
import DateTimePicker from '@react-native-community/datetimepicker';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Platform, Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

function toYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function parseYmd(s: string): Date {
  const [y, m, d] = s.split('-').map(Number);
  return new Date(y, (m ?? 1) - 1, d ?? 1);
}

type Step = 'form' | 'review' | 'done';

export const ReportAbsenceScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ReportAbsence'>>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const reasonCodes = useAttendanceReasonCodes();
  const report = useReportParentAbsence(studentId);

  const today = useMemo(() => toYmd(new Date()), []);
  const [startDate, setStartDate] = useState(today);
  const [endDate, setEndDate] = useState(today);
  const [reason, setReason] = useState('');
  const [reasonCodeId, setReasonCodeId] = useState<number | null>(null);
  const [step, setStep] = useState<Step>('form');
  const [picker, setPicker] = useState<'start' | 'end' | null>(null);
  const [resultSummary, setResultSummary] = useState<string | null>(null);

  if (studentId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Report absence" onBack={() => navigation.goBack()} />
        <EmptyState title="Missing student" message="No child was selected." icon="alert-circle-outline" />
      </ScreenContainer>
    );
  }

  const onSubmit = async () => {
    try {
      const res = await report.mutateAsync({
        start_date: startDate,
        end_date: endDate,
        reason: reason.trim(),
        reason_code_id: reasonCodeId,
      });
      const days = res.data?.school_days ?? 0;
      const skipped = res.data?.skipped?.length ?? 0;
      setResultSummary(
        `Submitted for ${days} school day${days === 1 ? '' : 's'}.${
          skipped > 0 ? ` ${skipped} non-school day(s) were skipped.` : ''
        }`,
      );
      setStep('done');
      showSuccess('Absence reported', res.message || 'The school has been notified.');
    } catch (err) {
      showError('Could not submit', err instanceof Error ? err.message : 'Please try again.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Report absence"
        subtitle={detail.data?.fullName ?? undefined}
        onBack={() => navigation.goBack()}
      />

      {step === 'done' ? (
        <View
          style={{
            padding: spacing.lg,
            borderRadius: radius.lg,
            borderWidth: 1,
            borderColor: palette.border,
            backgroundColor: palette.surface,
          }}
        >
          <Text style={{ color: colors.success, fontWeight: '700', fontSize: typography.title.fontSize }}>
            Submitted
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>
            {resultSummary ?? 'Your absence report was sent to the school.'}
          </Text>
          <Button label="Back to attendance" onPress={() => navigation.goBack()} style={{ marginTop: spacing.lg }} />
          <Button
            label="Report another"
            variant="secondary"
            onPress={() => {
              setStep('form');
              setReason('');
              setResultSummary(null);
            }}
            style={{ marginTop: spacing.sm }}
          />
        </View>
      ) : null}

      {step === 'review' ? (
        <View style={{ gap: spacing.md }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Review before sending</Text>
          <Text style={{ color: palette.textSecondary }}>
            Child: {detail.data?.fullName ?? `#${studentId}`}
          </Text>
          <Text style={{ color: palette.textSecondary }}>
            Dates: {startDate === endDate ? startDate : `${startDate} → ${endDate}`}
          </Text>
          <Text style={{ color: palette.textSecondary }}>Reason: {reason.trim()}</Text>
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            This request will be communicated to the class teacher and relevant school staff. Weekends and
            holidays are skipped automatically.
          </Text>
          <Button label="Confirm & submit" loading={report.isPending} onPress={() => void onSubmit()} />
          <Button label="Edit" variant="secondary" onPress={() => setStep('form')} disabled={report.isPending} />
          {report.isError ? (
            <Button
              label="Retry"
              variant="ghost"
              onPress={() => void onSubmit()}
              style={{ borderColor: colors.error, borderWidth: 1 }}
            />
          ) : null}
        </View>
      ) : null}

      {step === 'form' ? (
        <View style={{ gap: spacing.md }}>
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            Report that your child will be away for one school day or several consecutive school days. The
            school will be notified.
          </Text>

          <Pressable
            onPress={() => setPicker('start')}
            style={{
              padding: spacing.md,
              borderRadius: radius.md,
              borderWidth: 1,
              borderColor: palette.border,
              backgroundColor: palette.surface,
            }}
          >
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Start date</Text>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: 4 }}>{startDate}</Text>
          </Pressable>
          <Pressable
            onPress={() => setPicker('end')}
            style={{
              padding: spacing.md,
              borderRadius: radius.md,
              borderWidth: 1,
              borderColor: palette.border,
              backgroundColor: palette.surface,
            }}
          >
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>End date</Text>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: 4 }}>{endDate}</Text>
          </Pressable>

          {picker ? (
            <DateTimePicker
              value={parseYmd(picker === 'start' ? startDate : endDate)}
              mode="date"
              display={Platform.OS === 'ios' ? 'spinner' : 'default'}
              onChange={(_, date) => {
                if (Platform.OS !== 'ios') setPicker(null);
                if (!date) return;
                const ymd = toYmd(date);
                if (picker === 'start') {
                  setStartDate(ymd);
                  if (ymd > endDate) setEndDate(ymd);
                } else {
                  setEndDate(ymd < startDate ? startDate : ymd);
                }
              }}
            />
          ) : null}
          {Platform.OS === 'ios' && picker ? (
            <Button label="Done" variant="secondary" onPress={() => setPicker(null)} />
          ) : null}

          {reasonCodes.isLoading ? <SkeletonListRows count={2} /> : null}
          {reasonCodes.data && reasonCodes.data.length > 0 ? (
            <FilterChipRow label="Reason category (optional)">
              {reasonCodes.data.slice(0, 8).map((code) => (
                <FilterChip
                  key={code.id}
                  label={code.name}
                  active={reasonCodeId === code.id}
                  onPress={() => setReasonCodeId(reasonCodeId === code.id ? null : code.id)}
                />
              ))}
            </FilterChipRow>
          ) : null}

          <TextField
            label="Reason"
            value={reason}
            onChangeText={setReason}
            placeholder="Tell the school why your child will be absent"
            multiline
            numberOfLines={4}
          />

          <Button
            label="Review"
            onPress={() => {
              if (endDate < startDate) {
                showError('Invalid dates', 'End date must be on or after the start date.');
                return;
              }
              if (reason.trim().length < 3) {
                showError('Reason required', 'Please enter a reason (at least 3 characters).');
                return;
              }
              setStep('review');
            }}
          />
        </View>
      ) : null}
    </ScreenContainer>
  );
};
