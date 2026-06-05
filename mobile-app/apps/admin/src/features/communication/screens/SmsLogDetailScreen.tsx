import { useCommunicationLogs, useCan } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { capitalizeStatus, formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsLogDetail'>;

export const SmsLogDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { logId } = route.params;
  const canView = useCan('communication.view');
  const { colors, palette, spacing } = useTheme();
  const query = useCommunicationLogs({ enabled: canView, perPage: 100 });

  const log = useMemo(
    () => (query.data?.data ?? []).find((row) => row.id === logId),
    [query.data, logId],
  );

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

  if (!log) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="SMS detail" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>Log entry not found.</Text>
        <Pressable onPress={() => void query.refetch()} style={{ marginTop: 12 }}>
          <Text style={{ color: colors.primary }}>Retry</Text>
        </Pressable>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="SMS detail" onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Delivery"
        rows={[
          { label: 'Contact', value: log.contact ?? '—' },
          { label: 'Status', value: capitalizeStatus(log.status) },
          { label: 'Sent', value: formatDateTimeLabel(log.sent_at) },
          { label: 'Delivered', value: formatDateTimeLabel(log.delivered_at) },
        ]}
      />
      <View style={[styles.message, { borderColor: palette.border, marginTop: spacing.md }]}>
        <Text style={{ color: palette.textPrimary, lineHeight: 22 }}>{log.message ?? '—'}</Text>
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  message: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 16 },
});
