import { useCan, useStaffPerformanceReview } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { capitalizeStatus, formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<PeopleStackParamList, 'PerformanceReviewDetail'>;

export const PerformanceReviewDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { staffId, reviewId } = route.params;
  const canView = useCan('people.view');
  const { colors, palette, spacing } = useTheme();
  const query = useStaffPerformanceReview(staffId, reviewId, { enabled: canView });

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

  if (query.isError || !query.data) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error }}>{(query.error as Error)?.message ?? 'Not found'}</Text>
        <Pressable onPress={() => void query.refetch()}>
          <Text style={{ color: colors.primary, marginTop: 12 }}>Retry</Text>
        </Pressable>
      </ScreenContainer>
    );
  }

  const review = query.data;

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Performance review" onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Overview"
        rows={[
          { label: 'Period', value: `${formatDateLabel(review.review_period_start)} – ${formatDateLabel(review.review_period_end)}` },
          { label: 'Review date', value: formatDateLabel(review.review_date) },
          { label: 'Rating', value: review.overall_rating != null ? String(review.overall_rating) : '—' },
          { label: 'Reviewer', value: review.reviewer_name ?? '—' },
          { label: 'Status', value: capitalizeStatus(review.status) },
        ]}
      />
      {review.strengths ? (
        <FinanceFieldSection title="Strengths" rows={[{ label: 'Notes', value: review.strengths }]} />
      ) : null}
      {review.areas_for_improvement ? (
        <FinanceFieldSection title="Areas for improvement" rows={[{ label: 'Notes', value: review.areas_for_improvement }]} />
      ) : null}
      {review.comments ? (
        <FinanceFieldSection title="Comments" rows={[{ label: 'Staff', value: review.comments }]} />
      ) : null}
      {review.reviewer_comments ? (
        <FinanceFieldSection title="Reviewer comments" rows={[{ label: 'Notes', value: review.reviewer_comments }]} />
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
});
