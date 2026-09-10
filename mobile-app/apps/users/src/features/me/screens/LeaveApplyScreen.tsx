import { useCreateLeaveRequest, useCurrentUser, useLeaveTypes } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Platform, Pressable, ScrollView, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

function toYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function toHm(d: Date): string {
  const h = String(d.getHours()).padStart(2, '0');
  const m = String(d.getMinutes()).padStart(2, '0');
  return `${h}:${m}`;
}

function parseYmd(s: string): Date {
  const [y, m, d] = s.split('-').map(Number);
  return new Date(y, (m ?? 1) - 1, d ?? 1);
}

function parseHm(s: string): Date {
  const [h, m] = s.split(':').map(Number);
  const d = new Date();
  d.setHours(h ?? 8, m ?? 0, 0, 0);
  return d;
}

type PickerKind = 'startDate' | 'endDate' | 'startTime' | 'endTime' | null;

export const LeaveApplyScreen: React.FC = () => {
  const navigation = useNavigation();
  const { spacing, palette, typography, radius } = useTheme();
  const user = useCurrentUser();
  const typesQuery = useLeaveTypes();
  const createMutation = useCreateLeaveRequest();

  const today = useMemo(() => toYmd(new Date()), []);
  const [leaveTypeId, setLeaveTypeId] = useState<number | null>(null);
  const [startDate, setStartDate] = useState(today);
  const [endDate, setEndDate] = useState(today);
  const [startTime, setStartTime] = useState('08:00');
  const [endTime, setEndTime] = useState('17:00');
  const [includeTimes, setIncludeTimes] = useState(false);
  const [reason, setReason] = useState('');
  const [picker, setPicker] = useState<PickerKind>(null);

  const submit = async () => {
    if (!leaveTypeId || !startDate || !endDate) {
      showError('Missing fields', 'Leave type and dates are required.');
      return;
    }
    if (endDate < startDate) {
      showError('Invalid range', 'End date must be on or after the start date.');
      return;
    }
    if (includeTimes) {
      const startAt = `${startDate}T${startTime}:00`;
      const endAt = `${endDate}T${endTime}:00`;
      if (endAt <= startAt) {
        showError('Invalid time range', 'End date/time must be after start date/time.');
        return;
      }
    }
    try {
      await createMutation.mutateAsync({
        staff_id: user?.staffId ?? undefined,
        leave_type_id: leaveTypeId,
        start_date: startDate,
        end_date: endDate,
        start_time: includeTimes ? startTime : undefined,
        end_time: includeTimes ? endTime : undefined,
        reason: reason.trim() || undefined,
      });
      showSuccess('Submitted', 'Leave request sent for approval.');
      navigation.goBack();
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not submit leave.');
    }
  };

  const FieldButton = ({
    label,
    value,
    onPress,
  }: {
    label: string;
    value: string;
    onPress: () => void;
  }) => (
    <Pressable
      onPress={onPress}
      style={{
        padding: spacing.md,
        borderRadius: radius.md,
        borderWidth: 1,
        borderColor: palette.border,
        backgroundColor: palette.surface,
        marginBottom: spacing.sm,
      }}
    >
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{label}</Text>
      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: 4 }}>{value}</Text>
    </Pressable>
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Apply for leave" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
          Choose leave dates. Optionally add start and end times for partial-day or timed absences. Balance still
          counts working days.
        </Text>
        <FilterChipRow label="Leave type">
          {(typesQuery.data ?? []).map((t) => (
            <FilterChip
              key={t.id}
              label={t.name}
              active={leaveTypeId === t.id}
              onPress={() => setLeaveTypeId(t.id)}
            />
          ))}
        </FilterChipRow>

        <FieldButton label="Start date" value={startDate} onPress={() => setPicker('startDate')} />
        <FieldButton label="End date" value={endDate} onPress={() => setPicker('endDate')} />

        <FilterChipRow label="Times">
          <FilterChip
            label="Full day(s)"
            active={!includeTimes}
            onPress={() => setIncludeTimes(false)}
          />
          <FilterChip
            label="Include start & end time"
            active={includeTimes}
            onPress={() => setIncludeTimes(true)}
          />
        </FilterChipRow>

        {includeTimes ? (
          <View>
            <FieldButton label="Start time" value={startTime} onPress={() => setPicker('startTime')} />
            <FieldButton label="End time" value={endTime} onPress={() => setPicker('endTime')} />
          </View>
        ) : null}

        {picker ? (
          <DateTimePicker
            value={
              picker === 'startDate'
                ? parseYmd(startDate)
                : picker === 'endDate'
                  ? parseYmd(endDate)
                  : picker === 'startTime'
                    ? parseHm(startTime)
                    : parseHm(endTime)
            }
            mode={picker === 'startDate' || picker === 'endDate' ? 'date' : 'time'}
            display={Platform.OS === 'ios' ? 'spinner' : 'default'}
            onChange={(_, date) => {
              if (Platform.OS !== 'ios') setPicker(null);
              if (!date) return;
              if (picker === 'startDate') {
                const ymd = toYmd(date);
                setStartDate(ymd);
                if (ymd > endDate) setEndDate(ymd);
              } else if (picker === 'endDate') {
                const ymd = toYmd(date);
                setEndDate(ymd < startDate ? startDate : ymd);
              } else if (picker === 'startTime') {
                setStartTime(toHm(date));
              } else if (picker === 'endTime') {
                setEndTime(toHm(date));
              }
            }}
          />
        ) : null}
        {Platform.OS === 'ios' && picker ? (
          <Button label="Done" variant="secondary" onPress={() => setPicker(null)} style={{ marginBottom: spacing.sm }} />
        ) : null}

        <TextField label="Reason" value={reason} onChangeText={setReason} multiline />
        <Button
          label="Submit request"
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.md }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
