import {
  ASSESSMENT_FILTER_CATEGORIES,
  displayCategoryLabel,
  useAssessmentHistory,
  useCan,
  useStudentAcademicSummary,
  type AssessmentDisplayCategory,
} from '@erp/core';
import {
  AcademicScreenHeader,
  AssessmentCard,
  EmptyState,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { formatPercent } from '../utils/formatters';

type Props = StackScreenProps<AcademicsStackParamList, 'AssessmentHistory'>;

export const AssessmentHistoryScreen: React.FC<Props> = ({ route, navigation }) => {
  const { studentId, studentName } = route.params;
  const canView = useCan('academics.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [category, setCategory] = useState<AssessmentDisplayCategory>('all');

  const summaryQuery = useStudentAcademicSummary(studentId, { enabled: canView });
  const historyQuery = useAssessmentHistory(studentId, { category }, { enabled: canView });

  const rows = useMemo(
    () => historyQuery.data?.pages.flatMap((p) => p.rows) ?? [],
    [historyQuery.data],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view assessments."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={rows}
        keyExtractor={(item) => item.id}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={historyQuery.isRefetching && !historyQuery.isFetchingNextPage}
            onRefresh={() => void historyQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader
              title={studentName}
              subtitle="Assessment history"
              onBack={() => navigation.goBack()}
            />
            {summaryQuery.data ? (
              <View style={[styles.summary, { backgroundColor: palette.accent, borderRadius: radius.md, padding: spacing.sm, marginBottom: spacing.md }]}>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Exam average</Text>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {formatPercent(summaryQuery.data.examAverage)}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
                  {summaryQuery.data.marksRecordedCount} marks · {summaryQuery.data.totalAssessmentCount} events
                </Text>
              </View>
            ) : null}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
              {ASSESSMENT_FILTER_CATEGORIES.map((cat) => {
                const active = category === cat;
                return (
                  <Pressable
                    key={cat}
                    onPress={() => setCategory(cat)}
                    style={[
                      styles.chip,
                      {
                        backgroundColor: active ? colors.primary : palette.surface,
                        borderColor: active ? colors.primary : palette.border,
                        borderRadius: radius.full,
                        marginRight: spacing.xs,
                      },
                    ]}
                  >
                    <Text style={{ color: active ? palette.textOnPrimary : palette.textSecondary, fontSize: typography.caption.fontSize, fontWeight: '700' }}>
                      {displayCategoryLabel(cat)}
                    </Text>
                  </Pressable>
                );
              })}
            </ScrollView>
          </View>
        }
        renderItem={({ item }) => (
          <AssessmentCard
            data={{
              item,
              onPress: () => navigation.navigate('AssessmentDetail', { item, studentName }),
            }}
          />
        )}
        ListEmptyComponent={
          historyQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={5} />
          ) : historyQuery.isError ? (
            <ListEmptyState
              title="Could not load assessments"
              message={(historyQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void historyQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No assessments"
              message="No assessments found for this filter."
              icon="document-outline"
            />
          )
        }
        onEndReached={() => {
          if (historyQuery.hasNextPage && !historyQuery.isFetchingNextPage) {
            void historyQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.3}
        ListFooterComponent={
          historyQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} /> : null
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  summary: {},
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderWidth: StyleSheet.hairlineWidth },
});
