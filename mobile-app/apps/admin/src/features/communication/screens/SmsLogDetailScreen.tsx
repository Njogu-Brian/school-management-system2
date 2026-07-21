import { useCommunicationLog, useCan } from '@erp/core';
import { AcademicScreenHeader, EmptyState, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { capitalizeStatus, formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsLogDetail'>;

export const SmsLogDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { logId } = route.params;
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useCommunicationLog(logId, { enabled: canView });

  const log = query.data;

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need communication.view permission to view SMS details."
          icon="lock-closed-outline"
        />
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

  if (query.isError || !log) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="SMS detail" onBack={() => navigation.goBack()} />
        <EmptyState
          title="Log entry not found"
          message={(query.error as Error)?.message ?? 'This SMS log could not be loaded.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
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
      <View
        style={[
          styles.message,
          {
            borderColor: palette.border,
            marginTop: spacing.md,
            borderRadius: radius.card,
            padding: spacing.md,
          },
        ]}
      >
        <Text
          style={{
            color: palette.textPrimary,
            lineHeight: typography.body.lineHeight,
            fontSize: typography.body.fontSize,
          }}
        >
          {log.message ?? '—'}
        </Text>
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  message: { borderWidth: StyleSheet.hairlineWidth },
});
