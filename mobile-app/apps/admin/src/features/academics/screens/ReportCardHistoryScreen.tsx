import { useCan, useReportCards } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ListEmptyState,
  ReportCardCard,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { RefreshControl, ScrollView, StyleSheet } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'ReportCardHistory'>;

export const ReportCardHistoryScreen: React.FC<Props> = ({ route, navigation }) => {
  const { studentId, studentName } = route.params;
  const canView = useCan('academics.view') && useCan('report_cards.view');
  const { colors, spacing } = useTheme();
  const listQuery = useReportCards(studentId, { enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view report cards."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <AcademicScreenHeader
          title={studentName}
          subtitle="Report card history"
          onBack={() => navigation.goBack()}
        />
        {listQuery.isLoading ? (
          <SkeletonListRows variant="card" count={4} />
        ) : listQuery.isError ? (
          <ListEmptyState
            title="Could not load report cards"
            message={(listQuery.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void listQuery.refetch()}
          />
        ) : (listQuery.data ?? []).length === 0 ? (
          <EmptyState
            title="No report cards"
            message="No report cards found for this student."
            icon="document-outline"
          />
        ) : (
          (listQuery.data ?? []).map((rc) => (
            <ReportCardCard
              key={rc.id}
              card={{
                id: rc.id,
                termLabel: rc.class_name ? `${rc.class_name} · Term ${rc.term_id}` : `Term ${rc.term_id}`,
                status: rc.status,
                overallGrade: rc.overall_grade,
                overallPercentage: rc.overall_percentage,
                onPress: () =>
                  navigation.navigate('ReportCardDetail', {
                    reportCardId: rc.id,
                    studentName,
                  }),
              }}
            />
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
