import { useCan, useWeeklyReportDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  FinanceFieldSection,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { capitalizeStatus, formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'WeeklyReportDetail'>;

export const WeeklyReportDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { type, reportId } = route.params;
  const canView = useCan('reports.view');
  const { palette, spacing } = useTheme();
  const query = useWeeklyReportDetail(type, reportId, { enabled: canView });
  const report = query.data;

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Weekly report" onBack={() => navigation.goBack()} />
        <SkeletonListRows count={6} variant="compact" />
      </ScreenContainer>
    );
  }

  if (!report) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Weekly report" onBack={() => navigation.goBack()} />
        <ListEmptyState
          icon="document-text-outline"
          title="Report not found"
          message="This report may have been removed."
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={report.title}
        subtitle={report.subtitle ?? undefined}
        onBack={() => navigation.goBack()}
      />
      <FinanceFieldSection
        title="Summary"
        rows={[
          { label: 'Type', value: capitalizeStatus(report.type.replace(/_/g, ' ')) },
          { label: 'Week ending', value: formatDateLabel(report.week_ending) },
          { label: 'Campus', value: report.campus ?? '—' },
        ]}
      />
      <View style={{ marginTop: spacing.md }}>
        <FinanceFieldSection
          title="Details"
          rows={report.fields.map((f) => ({ label: f.label, value: f.value || '—' }))}
        />
      </View>
      {report.notes ? (
        <View style={[styles.notes, { borderColor: palette.border, marginTop: spacing.md }]}>
          <Text style={{ color: palette.textSecondary, fontSize: 12, fontWeight: '600', marginBottom: 4 }}>
            NOTES
          </Text>
          <Text style={{ color: palette.textPrimary, lineHeight: 20 }}>{report.notes}</Text>
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  notes: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, padding: 14 },
});
