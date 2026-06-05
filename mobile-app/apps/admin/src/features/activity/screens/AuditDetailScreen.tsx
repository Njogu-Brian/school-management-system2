import { useAuditTrailDetail } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<DashboardStackParamList, 'AuditDetail'>;

function formatJsonBlock(value: unknown): string {
  if (value == null) {
    return '—';
  }
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
}

export const AuditDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { auditId } = route.params;
  const { palette, spacing, fontSizes } = useTheme();
  const detailQuery = useAuditTrailDetail(auditId);

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator />
      </ScreenContainer>
    );
  }

  const record = detailQuery.data;
  if (!record) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Audit detail" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>Record not found.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Audit detail" onBack={() => navigation.goBack()} />
        <FinanceFieldSection
          title="Event"
          rows={[
            { label: 'Actor', value: record.user },
            { label: 'Action', value: record.action },
            { label: 'Module', value: record.module },
            { label: 'Target', value: record.target },
            { label: 'Time', value: formatDateTimeLabel(record.timestamp) },
            { label: 'Source', value: record.source },
          ]}
        />
        <Text style={{ fontWeight: '700', marginTop: spacing.md, color: palette.textPrimary }}>Before</Text>
        <Text style={[styles.json, { color: palette.textSecondary, fontSize: fontSizes.xs }]} selectable>
          {formatJsonBlock(record.before_values)}
        </Text>
        <Text style={{ fontWeight: '700', marginTop: spacing.md, color: palette.textPrimary }}>After</Text>
        <Text style={[styles.json, { color: palette.textSecondary, fontSize: fontSizes.xs }]} selectable>
          {formatJsonBlock(record.after_values)}
        </Text>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  json: { marginTop: 8, lineHeight: 18, fontFamily: 'monospace' },
});
