import { useStudentDetail, useStudentReportCards } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ListRowCard,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Text } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { ChildAcademicProgressSection } from '../components/ChildAcademicProgressSection';
import { formatShortDate } from '../utils/format';

export const ChildResultsScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildResults'>>();
  const { palette, spacing, typography } = useTheme();
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

      {studentId > 0 ? (
        <>
          <Text
            style={{
              color: palette.textSecondary,
              fontWeight: '700',
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
              textTransform: 'uppercase',
              letterSpacing: 0.4,
            }}
          >
            Progress
          </Text>
          <ChildAcademicProgressSection studentId={studentId} hideIdentity />
        </>
      ) : null}

      <Text
        style={{
          color: palette.textSecondary,
          fontWeight: '700',
          fontSize: typography.caption.fontSize,
          marginBottom: spacing.xs,
          textTransform: 'uppercase',
          letterSpacing: 0.4,
        }}
      >
        Report cards
      </Text>

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
          <ListRowCard
            key={card.id}
            title={`${card.class_name ?? 'Report card'} · ${card.term_name ?? `Term ${card.term_id}`}`}
            subtitle={
              card.access_locked
                ? 'Clear fees to view or download this report form'
                : 'Tap to download PDF or view report form'
            }
            meta={formatShortDate(card.generated_at ?? card.created_at)}
            icon="school-outline"
            glyph="chart"
            accent={card.access_locked ? 'warning' : 'info'}
            badge={card.access_locked ? 'Fees due' : card.status}
            badgeTone={card.access_locked ? 'warning' : card.status === 'published' ? 'success' : 'info'}
            onPress={() =>
              navigation.navigate('ReportCardDetail', {
                studentId,
                reportCardId: card.id,
              })
            }
          />
        ))
      )}
    </ScreenContainer>
  );
};
