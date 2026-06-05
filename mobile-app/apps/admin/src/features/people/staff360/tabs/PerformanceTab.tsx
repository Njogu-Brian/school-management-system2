import { useStaffPerformanceReviews } from '@erp/core';
import { EmptyState } from '@erp/ui';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { capitalizeStatus, formatDateLabel } from '../../../shared/utils/formatters';

export interface PerformanceTabProps {
  staffId: number;
  onOpenReview?: (reviewId: number) => void;
}

export const PerformanceTab: React.FC<PerformanceTabProps> = ({ staffId, onOpenReview }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useStaffPerformanceReviews(staffId);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: 24, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <View style={{ alignItems: 'center', paddingVertical: 16 }}>
        <Text style={{ color: colors.error }}>{(query.error as Error).message}</Text>
        <Pressable onPress={() => void query.refetch()} style={{ marginTop: 8 }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </View>
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
          style={[styles.card, { borderColor: palette.border, marginBottom: spacing.xs }]}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
            {formatDateLabel(review.review_date) ?? review.review_type ?? 'Review'}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
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
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12 },
});
