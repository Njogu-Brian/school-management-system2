import { useAcademicDashboard, useCan } from '@erp/core';
import {
  AcademicKpiCard,
  AcademicTrendCard,
  DashboardHero,
  DashboardSection,
  EmptyState,
  QuickAction,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { ExamBreakdownChart } from '../components/ExamBreakdownChart';

const SECTIONS = [
  { route: 'Assessments' as const, label: 'Assessments', icon: 'analytics-outline' as const },
  { route: 'ExamsList' as const, label: 'Exams', icon: 'school-outline' as const },
  { route: 'Marks' as const, label: 'Marks', icon: 'grid-outline' as const },
  { route: 'MarksMatrix' as const, label: 'Marks Matrix', icon: 'apps-outline' as const },
  { route: 'ReportCards' as const, label: 'Report Cards', icon: 'ribbon-outline' as const },
  { route: 'Moderation' as const, label: 'Moderation', icon: 'shield-checkmark-outline' as const },
  { route: 'CbcCurriculum' as const, label: 'CBC Curriculum', icon: 'library-outline' as const },
];

export const AcademicsDashboardScreen: React.FC = () => {
  const canView = useCan('academics.view');
  const navigation = useNavigation<StackNavigationProp<AcademicsStackParamList>>();
  const { colors, spacing } = useTheme();
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
        <EmptyState
          title="Access denied"
          message="You need academics.view permission to open the academics workspace."
          icon="lock-closed-outline"
        />
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

        {dashboardQuery.isError ? (
          <EmptyState
            title="Could not load dashboard"
            message={(dashboardQuery.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void dashboardQuery.refetch()}
          />
        ) : null}

        {Object.keys(breakdown).length > 0 ? (
          <View style={{ marginTop: spacing.md, marginBottom: spacing.md }}>
            <ExamBreakdownChart breakdown={breakdown} />
          </View>
        ) : null}

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
  actions: { flexDirection: 'row', flexWrap: 'wrap' },
});
