import { useCan, useCheckOutVisitor, useVisitors } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'VisitorDetail'>;

export const VisitorDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { visitorId } = route.params;
  const canView = useCan('operations.view');
  const { colors, palette, spacing } = useTheme();
  const query = useVisitors({ enabled: canView });
  const checkoutMutation = useCheckOutVisitor();

  const visitor = useMemo(
    () => (query.data ?? []).find((v) => v.id === visitorId),
    [query.data, visitorId],
  );

  const onCheckout = () => {
    if (!visitor) return;
    confirmAction('Check out', `Check out ${visitor.visitor_name}?`, 'Confirm', async () => {
      await checkoutMutation.mutateAsync(visitor.id);
      showSuccess('Checked out', undefined, () => navigation.goBack());
    });
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (!visitor) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Visitor" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>Visitor not found.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={visitor.visitor_name} onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Visit"
        rows={[
          { label: 'Phone', value: visitor.phone ?? '—' },
          { label: 'Organization', value: visitor.organization ?? '—' },
          { label: 'Purpose', value: visitor.purpose ?? '—' },
          { label: 'Host', value: visitor.host_name ?? '—' },
          { label: 'Check-in', value: formatDateTimeLabel(visitor.checked_in_at) },
          { label: 'Check-out', value: formatDateTimeLabel(visitor.checked_out_at) },
          { label: 'Status', value: visitor.on_site ? 'On site' : 'Checked out' },
        ]}
      />
      {visitor.notes ? (
        <View style={[styles.notes, { borderColor: palette.border, marginTop: spacing.md }]}>
          <Text style={{ color: palette.textSecondary }}>{visitor.notes}</Text>
        </View>
      ) : null}
      {visitor.on_site ? (
        <Pressable
          onPress={onCheckout}
          disabled={checkoutMutation.isPending}
          style={[styles.btn, { backgroundColor: colors.primary, marginTop: spacing.lg }]}
        >
          <Text style={{ color: '#fff', fontWeight: '700' }}>
            {checkoutMutation.isPending ? 'Checking out…' : 'Check out visitor'}
          </Text>
        </Pressable>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  notes: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12 },
  btn: { padding: 14, borderRadius: 8, alignItems: 'center' },
});
