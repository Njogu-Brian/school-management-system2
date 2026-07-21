import { useStaffPerformanceReviews } from '@erp/core';
import { EmptyState, useTheme } from '@erp/ui';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { capitalizeStatus, formatDateLabel } from '../../../shared/utils/formatters';

export interface PerformanceTabProps {
  staffId: number;
  onOpenReview?: (reviewId: number) => void;
}

export const PerformanceTab: React.FC<PerformanceTabProps> = ({ staffId, onOpenReview }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useStaffPerformanceReviews(staffId);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Could not load reviews"
        message={(query.error as Error).message}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }

  const reviews = query.data ?? [];
  if (reviews.length === 0) {
    return (
      <EmptyState
        title="No performance reviews"
        message="No appraisals have been recorded for this staff member yet."
        icon="trophy-outline"
      />
    );
  }

  return (
    <>
      {reviews.map((review) => (
        <Pressable
          key={review.id}
          onPress={() => onOpenReview?.(review.id)}
          style={[
            styles.card,
            {
              borderColor: palette.borderSubtle,
              backgroundColor: palette.surfaceRaised,
              borderRadius: radius.card,
              padding: spacing.md,
              marginBottom: spacing.sm,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: '600',
              fontSize: typography.body.fontSize,
            }}
          >
            {formatDateLabel(review.review_date) ?? review.review_type ?? 'Review'}
          </Text>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              marginTop: 4,
            }}
          >
            {[
              review.overall_rating != null ? `Rating ${review.overall_rating}` : null,
              capitalizeStatus(review.status),
              review.reviewer_name,
            ]
              .filter(Boolean)
              .join(' · ')}
          </Text>
        </Pressable>
      ))}
    </>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
});
