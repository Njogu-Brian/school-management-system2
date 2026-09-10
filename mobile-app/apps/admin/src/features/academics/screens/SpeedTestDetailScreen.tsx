import { useSpeedTest } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, SkeletonListRows, useTheme } from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Route = RouteProp<AcademicsStackParamList, 'SpeedTestDetail'>;

export const SpeedTestDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const { batchKey, title } = useRoute<Route>().params;
  const { palette, spacing, typography, radius } = useTheme();
  const detailQuery = useSpeedTest(batchKey);
  const data = detailQuery.data;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={title || data?.title || 'Speed test'}
        subtitle={
          data
            ? `${data.subject_name ?? 'Subject'} · ${data.classroom_name ?? ''} · ${data.question_count ?? '—'} questions · out of ${data.max_marks ?? '—'}`
            : undefined
        }
        onBack={() => navigation.goBack()}
      />
      {detailQuery.isLoading ? (
        <SkeletonListRows count={8} />
      ) : detailQuery.isError ? (
        <EmptyState
          title="Could not load speed test"
          message={detailQuery.error instanceof Error ? detailQuery.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (
        (data?.entries ?? []).map((row) => {
          const score =
            row.score != null && row.out_of != null
              ? `${row.score}/${row.out_of}`
              : row.score_percent != null
                ? `${row.score_percent.toFixed(0)}%`
                : 'Not marked';
          return (
            <View
              key={row.student_id}
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{row.student_name}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {score}
              </Text>
            </View>
          );
        })
      )}
    </ScreenContainer>
  );
};
