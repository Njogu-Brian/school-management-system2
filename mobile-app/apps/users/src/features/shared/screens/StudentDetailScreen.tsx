import { useCurrentUser, useStudentDetail, UserRole } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  QuickAction,
  ScreenContainer,
  StudentStatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';

type DetailParams = { studentId: number };
type LooseNav = { navigate: (name: string, params?: object) => void };

/**
 * Shared student profile — used by Teacher (class-teacher / subject-teacher scope)
 * and reachable from Parent stacks too. Shows pastoral info only: never renders
 * fee balances/amounts, just the cleared/pending badge.
 */
export const StudentDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = (route.params as DetailParams | undefined)?.studentId ?? 0;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

  const isTeacher =
    user?.role === UserRole.TEACHER ||
    user?.role === UserRole.SENIOR_TEACHER ||
    user?.role === UserRole.SUPERVISOR;

  const data = detail.data;
  const guardianContacts = data?.guardians ?? [];
  const parentInfo = data?.parent;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Student"
        onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
      />
      {studentId <= 0 ? (
        <EmptyState title="Missing student" message="No student was selected." icon="alert-circle-outline" />
      ) : detail.isError ? (
        <EmptyState
          title="Could not load"
          message={detail.error instanceof Error ? detail.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (
        <View>
          <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '700' }}>
            {data?.fullName ?? (detail.isLoading ? 'Loading…' : `Student #${studentId}`)}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: spacing.xs }}>
            {[data?.admissionNumber, data?.className, data?.streamName].filter(Boolean).join(' · ') ||
              'Scoped student profile'}
          </Text>

          {data ? (
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md }}>
              <StudentStatusBadge kind="enrollment" enrollmentStatus={data.enrollmentStatus} />
              <StudentStatusBadge kind="fee" feeStatus={data.feeStatus} />
            </View>
          ) : null}

          {data ? (
            <View
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginTop: spacing.lg,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
                Guardian contact
              </Text>
              {guardianContacts.length > 0 ? (
                guardianContacts.map((g) => (
                  <View key={g.id} style={{ marginBottom: spacing.sm }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                      {g.name} {g.isPrimary ? '· Primary' : ''}
                    </Text>
                    <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                      {[g.relationship, g.phone].filter(Boolean).join(' · ')}
                    </Text>
                  </View>
                ))
              ) : parentInfo ? (
                <View>
                  {parentInfo.guardianName || parentInfo.fatherName || parentInfo.motherName ? (
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                      {parentInfo.guardianName ?? parentInfo.fatherName ?? parentInfo.motherName}
                    </Text>
                  ) : null}
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                    {[
                      parentInfo.guardianPhone ?? parentInfo.fatherPhone ?? parentInfo.motherPhone,
                      parentInfo.guardianRelationship,
                    ]
                      .filter(Boolean)
                      .join(' · ') || 'No contact on file'}
                  </Text>
                </View>
              ) : (
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                  No guardian contact on file.
                </Text>
              )}
            </View>
          ) : null}

          {data ? (
            <View style={{ marginTop: spacing.lg }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
                Quick actions
              </Text>
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
                <QuickAction
                  label="Diary"
                  icon="chatbubbles-outline"
                  onPress={() =>
                    (navigation as unknown as LooseNav).navigate('DiaryChat', {
                      studentId,
                      studentName: data.fullName,
                    })
                  }
                />
                {isTeacher ? (
                  <QuickAction
                    label="Mark attendance"
                    icon="checkbox-outline"
                    onPress={() => (navigation as unknown as LooseNav).navigate('MarkAttendance')}
                  />
                ) : null}
                {isTeacher ? (
                  <QuickAction
                    label="Transport"
                    icon="bus-outline"
                    onPress={() => (navigation as unknown as LooseNav).navigate('TeacherTransportHub')}
                  />
                ) : null}
              </View>
            </View>
          ) : null}
        </View>
      )}
    </ScreenContainer>
  );
};
