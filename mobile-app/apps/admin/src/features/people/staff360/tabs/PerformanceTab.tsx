import { useStaffPerformanceReviews } from '@erp/core';
import { EmptyState, FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface PerformanceTabProps {
  staffId: number;
}

export const PerformanceTab: React.FC<PerformanceTabProps> = ({ staffId }) => {
  const { colors, palette, fontSizes } = useTheme();
  const query = useStaffPerformanceReviews(staffId);

  const rows = useMemo(
    () =>
      (query.data ?? []).map((review) => ({
        label: review.review_date ?? review.review_type ?? 'Review',
        value: [
          review.overall_rating != null ? `Rating ${review.overall_rating}` : null,
          review.status,
          review.reviewer_name,
        ]
          .filter(Boolean)
          .join(' · ') || '—',
      })),
    [query.data],
  );

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

  if (rows.length === 0) {
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
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 8 }}>
        API: GET /staff/{'{id}'}/performance-reviews
      </Text>
      <FinanceFieldSection title="Performance reviews" rows={rows} />
    </>
  );
};
