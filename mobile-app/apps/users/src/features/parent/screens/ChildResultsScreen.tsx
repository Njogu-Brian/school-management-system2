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
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Text, Pressable, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { ChildAcademicProgressSection } from '../components/ChildAcademicProgressSection';
import { formatShortDate } from '../utils/format';

export const ChildResultsScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
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
          <Pressable
            key={card.id}
            onPress={() =>
              navigation.navigate('ReportCardDetail', {
                studentId,
                reportCardId: card.id,
              })
            }
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
                {card.class_name ?? 'Report card'} · {card.term_name ?? `Term ${card.term_id}`}
              </Text>
              <StatusBadge
                label={card.access_locked ? 'Fees due' : card.status}
                tone={card.access_locked ? 'warning' : card.status === 'published' ? 'success' : 'info'}
              />
            </View>
            {card.access_locked ? (
              <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                Clear fees to view or download this report form
              </Text>
            ) : (
              <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                Tap to download PDF or view report form
              </Text>
            )}
            <Text style={{ color: palette.textMuted, marginTop: 4, fontSize: typography.caption.fontSize }}>
              {formatShortDate(card.generated_at ?? card.created_at)}
            </Text>
          </Pressable>
        ))
      )}
    </ScreenContainer>
  );
};
