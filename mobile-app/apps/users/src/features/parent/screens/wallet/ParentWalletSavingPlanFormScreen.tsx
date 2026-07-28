import { useCreateSavingPlan, useParentWalletSavingPlans, useUpdateSavingPlan } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../../shared/utils/feedback';

const DAYS = [
  { label: 'Sun', value: 0 },
  { label: 'Mon', value: 1 },
  { label: 'Tue', value: 2 },
  { label: 'Wed', value: 3 },
  { label: 'Thu', value: 4 },
  { label: 'Fri', value: 5 },
  { label: 'Sat', value: 6 },
];

export const ParentWalletSavingPlanFormScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'WalletSavingPlanForm'>>();
  const planId = route.params?.planId;
  const { palette, spacing, typography, radius, colors } = useTheme();
  const plansQuery = useParentWalletSavingPlans();
  const create = useCreateSavingPlan();
  const update = useUpdateSavingPlan();

  const existing = useMemo(
    () => (plansQuery.data ?? []).find((p) => p.id === planId),
    [plansQuery.data, planId],
  );

  const [amount, setAmount] = useState('500');
  const [dayOfWeek, setDayOfWeek] = useState(1);
  const [time, setTime] = useState('08:00');
  const [label, setLabel] = useState('');

  useEffect(() => {
    if (!existing) return;
    setAmount(String(existing.amount));
    setDayOfWeek(existing.day_of_week);
    setTime(existing.remind_at_time);
    setLabel(existing.label ?? '');
  }, [existing]);

  const submit = async () => {
    const value = Number(amount);
    if (!value || value < 1) {
      showError('Amount', 'Enter a weekly amount.');
      return;
    }
    if (!/^\d{2}:\d{2}$/.test(time)) {
      showError('Time', 'Use HH:MM (24-hour), e.g. 08:00.');
      return;
    }
    try {
      if (planId) {
        await update.mutateAsync({
          id: planId,
          amount: value,
          day_of_week: dayOfWeek,
          remind_at_time: time,
          label: label.trim() || null,
          active: true,
        });
        showSuccess('Updated', 'Saving plan saved.');
      } else {
        await create.mutateAsync({
          amount: value,
          day_of_week: dayOfWeek,
          remind_at_time: time,
          label: label.trim() || undefined,
          active: true,
        });
        showSuccess('Created', 'You will get a weekly reminder.');
      }
      navigation.goBack();
    } catch (err) {
      showError('Save failed', err instanceof Error ? err.message : 'Could not save plan.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={planId ? 'Edit saving plan' : 'New saving plan'}
        onBack={() => navigation.goBack()}
      />
      <TextField label="Label (optional)" value={label} onChangeText={setLabel} placeholder="School fees" />
      <TextField
        label="Weekly amount (KES)"
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
      />
      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>Day</Text>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs, marginBottom: spacing.md }}>
        {DAYS.map((d) => {
          const active = d.value === dayOfWeek;
          return (
            <Pressable
              key={d.value}
              onPress={() => setDayOfWeek(d.value)}
              style={{
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.sm,
                borderRadius: radius.full,
                backgroundColor: active ? colors.primary : palette.surface,
                borderWidth: 1,
                borderColor: active ? colors.primary : palette.border,
              }}
            >
              <Text style={{ color: active ? '#fff' : palette.textPrimary, fontWeight: '600' }}>{d.label}</Text>
            </Pressable>
          );
        })}
      </View>
      <TextField
        label="Reminder time (HH:MM)"
        value={time}
        onChangeText={setTime}
        placeholder="08:00"
        autoCapitalize="none"
      />
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
        You will get a notification. Tap it to send an STK for this amount into your wallet (due fees first).
      </Text>
      <Button
        label="Save plan"
        loading={create.isPending || update.isPending}
        onPress={() => void submit()}
      />
    </ScreenContainer>
  );
};
