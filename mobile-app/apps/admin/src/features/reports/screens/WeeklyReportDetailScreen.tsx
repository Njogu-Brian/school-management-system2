import { useCan, useWeeklyReports } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { capitalizeStatus, formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'WeeklyReportDetail'>;

export const WeeklyReportDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { type, reportId } = route.params;
  const canView = useCan('reports.view');
  const { palette, spacing } = useTheme();
  const query = useWeeklyReports({ enabled: canView });

  const report = useMemo(
    () => (query.data?.items ?? []).find((r) => r.type === type && r.id === reportId),
    [query.data, type, reportId],
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
        <ActivityIndicator />
      </ScreenContainer>
    );
  }

  if (!report) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Weekly report" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>Report not found.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={report.title} onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Report"
        rows={[
          { label: 'Type', value: capitalizeStatus(report.type.replace(/_/g, ' ')) },
          { label: 'Week ending', value: formatDateLabel(report.week_ending) },
          { label: 'Campus', value: report.campus ?? '—' },
          { label: 'Subtitle', value: report.subtitle ?? '—' },
          {
            label: 'Status',
            value: report.resolved != null ? (report.resolved ? 'Resolved' : 'Open') : '—',
          },
        ]}
      />
      <Text style={{ color: palette.textSecondary, fontSize: 12, marginTop: spacing.lg }}>
        Full report body and download are available on the web portal.
      </Text>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
