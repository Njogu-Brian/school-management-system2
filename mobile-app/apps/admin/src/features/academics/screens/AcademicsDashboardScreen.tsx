import { useAcademicDashboard, useCan } from '@erp/core';
import {
  AcademicKpiCard,
  AcademicTrendCard,
  DashboardHero,
  DashboardSection,
  KpiCard,
  QuickAction,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { ExamBreakdownChart } from '../components/ExamBreakdownChart';

const KPI_CONFIG = [
  { key: 'examsDraft' as const, label: 'Exams Draft', icon: 'document-outline' as const },
  { key: 'examsMarking' as const, label: 'Exams Marking', icon: 'create-outline' as const },
  { key: 'examsModeration' as const, label: 'Exams Moderation', icon: 'git-compare-outline' as const },
  { key: 'examsPublished' as const, label: 'Exams Published', icon: 'checkmark-circle-outline' as const },
  { key: 'lessonPlansPendingReview' as const, label: 'Lesson Plans Pending', icon: 'time-outline' as const },
];

const SECTIONS = [
  { route: 'Assessments' as const, label: 'Assessments', icon: 'analytics-outline' as const },
  { route: 'ExamsList' as const, label: 'Exams', icon: 'school-outline' as const },
  { route: 'Marks' as const, label: 'Marks', icon: 'grid-outline' as const },
  { route: 'MarksMatrix' as const, label: 'Marks Matrix', icon: 'apps-outline' as const },
  { route: 'ReportCards' as const, label: 'Report Cards', icon: 'ribbon-outline' as const },
  { route: 'Moderation' as const, label: 'Moderation', icon: 'shield-checkmark-outline' as const },
];

export const AcademicsDashboardScreen: React.FC = () => {
  const canView = useCan('academics.view');
  const navigation = useNavigation<StackNavigationProp<AcademicsStackParamList>>();
  const { colors, palette, spacing, typography } = useTheme();
  const dashboardQuery = useAcademicDashboard({ enabled: canView });

  const openSection = useCallback(
    (route: (typeof SECTIONS)[number]['route']) => {
      navigation.navigate(route);
    },
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize, textAlign: 'center' }}>
          You need academics.view permission to open the academics workspace.
        </Text>
      </ScreenContainer>
    );
  }

  const breakdown = dashboardQuery.data?.examStatusBreakdown ?? {};
  const totalExams = Object.values(breakdown).reduce((sum, n) => sum + n, 0);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={dashboardQuery.isRefetching}
            onRefresh={() => void dashboardQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <DashboardHero
          variant="academics"
          title="Academics Dashboard"
          subtitle="Exams, marks & report cards"
          meta={totalExams > 0 ? `${totalExams} exams in pipeline` : undefined}
        />

        <WidgetGrid>
          {KPI_CONFIG.map((kpi) => {
            const raw = dashboardQuery.data?.[kpi.key];
            const value = String(raw ?? 0);
            const state = dashboardQuery.isLoading
              ? 'loading'
              : dashboardQuery.isError
                ? 'error'
                : raw == null
                  ? 'empty'
                  : 'success';
            return (
              <WidgetShell
                key={kpi.key}
                state={state}
                title={kpi.label}
                onRetry={() => void dashboardQuery.refetch()}
              >
                <KpiCard label={kpi.label} value={value} icon={kpi.icon} />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        {dashboardQuery.isError ? (
          <Pressable onPress={() => void dashboardQuery.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.error, textAlign: 'center' }}>
              {(dashboardQuery.error as Error).message}
            </Text>
          </Pressable>
        ) : null}

        {Object.keys(breakdown).length > 0 ? (
          <View style={{ marginTop: spacing.md, marginBottom: spacing.md }}>
            <ExamBreakdownChart breakdown={breakdown} />
          </View>
        ) : null}

        <DashboardSection title="Exam Status Breakdown">
          <View style={[styles.breakdown, { gap: spacing.xs }]}>
            {Object.keys(breakdown).length === 0 ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                No exam data loaded.
              </Text>
            ) : (
              Object.entries(breakdown).map(([status, count]) => (
                <AcademicKpiCard key={status} label={status} value={String(count)} />
              ))
            )}
          </View>
        </DashboardSection>

        {(dashboardQuery.data?.trendSummary?.length ?? 0) > 0 ? (
          <DashboardSection title="Academic Trend Summary">
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              {dashboardQuery.data!.trendSummary.map((t) => (
                <AcademicTrendCard
                  key={t.exam_id}
                  examName={t.exam}
                  mean={t.mean}
                  passRate={t.pass_rate}
                />
              ))}
            </ScrollView>
          </DashboardSection>
        ) : null}

        <DashboardSection title="Moderation Queue">
          <Pressable onPress={() => openSection('Moderation')}>
            <AcademicKpiCard
              label="Lesson plans awaiting review"
              value={String(dashboardQuery.data?.lessonPlansPendingReview ?? 0)}
              icon="time-outline"
            />
          </Pressable>
        </DashboardSection>

        <DashboardSection title="Workspace">
          <View style={[styles.actions, { gap: spacing.sm }]}>
            {SECTIONS.map((section) => (
              <QuickAction
                key={section.route}
                label={section.label}
                icon={section.icon}
                onPress={() => openSection(section.route)}
              />
            ))}
          </View>
        </DashboardSection>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  breakdown: { flexDirection: 'row', flexWrap: 'wrap' },
  actions: { flexDirection: 'row', flexWrap: 'wrap' },
});
