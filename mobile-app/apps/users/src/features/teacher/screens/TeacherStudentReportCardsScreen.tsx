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
import { Pressable, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { formatShortDate } from '../../shared/utils/format';

export const TeacherStudentReportCardsScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<TeacherStackParamList>>();
  const route = useRoute<RouteProp<TeacherStackParamList, 'StudentReportCards'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const reportCards = useStudentReportCards(studentId);
  const subtitle = route.params.studentName ?? detail.data?.fullName ?? undefined;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Report forms"
        subtitle={subtitle}
        onBack={() => navigation.goBack()}
      />

      {reportCards.isLoading ? (
        <SkeletonListRows count={4} />
      ) : reportCards.isError ? (
        <EmptyState
          title="Could not load report forms"
          message={reportCards.error instanceof Error ? reportCards.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (reportCards.data ?? []).length === 0 ? (
        <EmptyState
          title="No report forms"
          message="Report cards for this student will appear here once generated."
          icon="school-outline"
        />
      ) : (
        (reportCards.data ?? []).map((card) => (
          <Pressable
            key={card.id}
            onPress={() =>
              navigation.navigate('TeacherReportCardDetail', {
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
                {card.class_name ?? 'Report card'} · Term {card.term_id}
              </Text>
              <StatusBadge
                label={card.status}
                tone={card.status === 'published' ? 'success' : 'info'}
              />
            </View>
            <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
              Tap to view report form and confirm marks
            </Text>
            <Text style={{ color: palette.textMuted, marginTop: 4, fontSize: typography.caption.fontSize }}>
              {formatShortDate(card.generated_at ?? card.created_at)}
            </Text>
          </Pressable>
        ))
      )}
    </ScreenContainer>
  );
};
