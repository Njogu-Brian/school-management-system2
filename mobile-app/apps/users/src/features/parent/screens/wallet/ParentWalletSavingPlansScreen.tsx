import { useDeleteSavingPlan, useParentWalletSavingPlans, usePaySavingPlanNow, useCurrentUser } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  SurfaceCard,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../../navigation/parent/parentStackTypes';
import { confirmAction, showError, showSuccess } from '../../../shared/utils/feedback';
import { formatKes } from '../../utils/format';

const DAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export const ParentWalletSavingPlansScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const { palette, spacing, typography } = useTheme();
  const plansQuery = useParentWalletSavingPlans();
  const remove = useDeleteSavingPlan();
  const payNow = usePaySavingPlanNow();
  const user = useCurrentUser();
  const [phone, setPhone] = useState(user?.phone ?? '');

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Saving plans" onBack={() => navigation.goBack()} />
      <Button
        label="New saving plan"
        onPress={() => navigation.navigate('WalletSavingPlanForm')}
        style={{ marginBottom: spacing.md }}
      />
      <TextField
        label="M-Pesa phone for reminders"
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
        placeholder="07XX XXX XXX"
      />

      {plansQuery.isLoading ? (
        <SkeletonListRows count={3} />
      ) : (plansQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No saving plans"
          message="Set a weekly amount and reminder time."
          icon="calendar-outline"
        />
      ) : (
        (plansQuery.data ?? []).map((plan) => (
          <SurfaceCard key={plan.id} accent={plan.active ? 'success' : 'warning'}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
              {plan.label || 'Weekly save'} · {formatKes(plan.amount)}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
              {DAY_LABELS[plan.day_of_week] ?? 'Day'} at {plan.remind_at_time}
              {plan.active ? '' : ' · paused'}
            </Text>
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm, flexWrap: 'wrap' }}>
              <Button
                label="Save now"
                loading={payNow.isPending}
                onPress={() => {
                  if (!phone.trim()) {
                    showError('Phone', 'Enter an M-Pesa number first.');
                    return;
                  }
                  void payNow
                    .mutateAsync({ id: plan.id, phone_number: phone.trim() })
                    .then(() => showSuccess('STK sent', 'Complete the prompt on your phone.'))
                    .catch((err) => showError('STK failed', err instanceof Error ? err.message : 'Try again.'));
                }}
              />
              <Button
                label="Edit"
                variant="secondary"
                onPress={() => navigation.navigate('WalletSavingPlanForm', { planId: plan.id })}
              />
              <Pressable
                onPress={() =>
                  confirmAction('Delete plan', 'Remove this saving plan?', 'Delete', () => {
                    void remove.mutateAsync(plan.id).then(() => showSuccess('Deleted'));
                  }, true)
                }
              >
                <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>Delete</Text>
              </Pressable>
            </View>
          </SurfaceCard>
        ))
      )}
    </ScreenContainer>
  );
};
