import { useCan, useReportCards } from '@erp/core';
import { AcademicScreenHeader, ReportCardCard, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'ReportCardHistory'>;

export const ReportCardHistoryScreen: React.FC<Props> = ({ route, navigation }) => {
  const { studentId, studentName } = route.params;
  const canView = useCan('academics.view') && useCan('report_cards.view');
  const { colors, palette, spacing } = useTheme();
  const listQuery = useReportCards(studentId, { enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
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
          <ActivityIndicator color={colors.primary} />
        ) : listQuery.isError ? (
          <Pressable onPress={() => void listQuery.refetch()}>
            <Text style={{ color: colors.error }}>{(listQuery.error as Error).message}</Text>
          </Pressable>
        ) : (listQuery.data ?? []).length === 0 ? (
          <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No report cards found.</Text>
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
