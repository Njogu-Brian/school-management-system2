import { useStudentDetail, useStudentReportCards } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatShortDate } from '../utils/format';

export const ChildResultsScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildResults'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const reportCards = useStudentReportCards(studentId);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Results"
        subtitle={detail.data?.fullName ?? undefined}
        onBack={() => navigation.goBack()}
      />

      {reportCards.isLoading ? (
        <SkeletonListRows count={4} />
      ) : reportCards.isError ? (
        <EmptyState
          title="Could not load results"
          message={reportCards.error instanceof Error ? reportCards.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (reportCards.data ?? []).length === 0 ? (
        <EmptyState
          title="No report cards"
          message="Published report cards for this child will appear here."
          icon="school-outline"
        />
      ) : (
        (reportCards.data ?? []).map((card) => (
          <View
            key={card.id}
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.sm,
            }}
          >
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>
                {card.class_name ?? 'Report card'} · Term {card.term_id}
              </Text>
              <StatusBadge
                label={card.status}
                tone={card.status === 'published' ? 'success' : 'info'}
              />
            </View>
            <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
              Overall {card.overall_percentage?.toFixed?.(1) ?? card.overall_percentage}%
              {card.overall_grade ? ` · Grade ${card.overall_grade}` : ''}
            </Text>
            <Text style={{ color: palette.textMuted, marginTop: 4, fontSize: typography.caption.fontSize }}>
              {formatShortDate(card.generated_at ?? card.created_at)}
            </Text>
          </View>
        ))
      )}
    </ScreenContainer>
  );
};
