import { useDriverBoarding, useDriverTripActions } from '@erp/core';
import type { DriverBoardingStatus } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { DriverStackParamList } from '../../../navigation/driver/driverStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<DriverStackParamList>;
type Route = RouteProp<DriverStackParamList, 'BoardingChecklist'>;

type MarkableStatus = Exclude<DriverBoardingStatus, 'pending'>;

function boardingTone(status: DriverBoardingStatus): 'success' | 'danger' | 'warning' | 'info' {
  switch (status) {
    case 'present':
      return 'success';
    case 'absent':
      return 'danger';
    case 'late':
      return 'warning';
    default:
      return 'info';
  }
}

export const BoardingChecklistScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const tripId = route.params.tripId;
  const { palette, spacing, typography, radius } = useTheme();
  const boardingQuery = useDriverBoarding(tripId);
  const { board } = useDriverTripActions(tripId);
  const [pendingStudentId, setPendingStudentId] = useState<number | null>(null);

  const students = boardingQuery.data?.students ?? [];
  const summary = useMemo(() => {
    const boarded = students.filter((s) => s.status === 'present' || s.status === 'late').length;
    const absent = students.filter((s) => s.status === 'absent').length;
    const pending = students.filter((s) => s.status === 'pending').length;
    if (boardingQuery.data?.boarded_count != null && boardingQuery.data?.total_count != null) {
      return {
        boarded: boardingQuery.data.boarded_count,
        absent,
        pending: Math.max(0, boardingQuery.data.total_count - boardingQuery.data.boarded_count - absent),
      };
    }
    return { boarded, absent, pending };
  }, [boardingQuery.data, students]);

  const mark = async (studentId: number, status: MarkableStatus, studentName: string) => {
    setPendingStudentId(studentId);
    try {
      await board.mutateAsync({ student_id: studentId, status });
      showSuccess('Boarding updated', `${studentName} marked ${status}.`);
      void boardingQuery.refetch();
    } catch (err) {
      showError('Could not update boarding', err instanceof Error ? err.message : 'Try again.');
    } finally {
      setPendingStudentId(null);
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Boarding checklist"
        subtitle={boardingQuery.data?.direction ?? `Trip #${tripId}`}
        onBack={() => navigation.goBack()}
      />

      {boardingQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : boardingQuery.isError ? (
        <EmptyState
          title="Could not load roster"
          message={boardingQuery.error instanceof Error ? boardingQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void boardingQuery.refetch()}
        />
      ) : (
        <>
          <View
            style={{
              flexDirection: 'row',
              flexWrap: 'wrap',
              gap: spacing.sm,
              marginBottom: spacing.md,
            }}
          >
            <StatusBadge label={`${summary.boarded} boarded`} tone="success" compact />
            <StatusBadge label={`${summary.absent} absent`} tone="danger" compact />
            <StatusBadge label={`${summary.pending} pending`} tone="warning" compact />
          </View>

          {students.length === 0 ? (
            <EmptyState title="No students" message="No students are assigned to this trip." icon="people-outline" />
          ) : (
            students.map((student) => {
              const busy = pendingStudentId === student.student_id && board.isPending;
              const name = student.full_name ?? `Student #${student.student_id}`;
              return (
                <View
                  key={student.student_id}
                  style={{
                    backgroundColor: palette.surface,
                    borderColor: palette.border,
                    borderWidth: 1,
                    borderRadius: radius.md,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                  }}
                >
                  <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>{name}</Text>
                    <StatusBadge label={student.status} tone={boardingTone(student.status)} compact />
                  </View>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    {[student.admission_number, student.drop_point].filter(Boolean).join(' · ')}
                  </Text>
                  <View style={{ flexDirection: 'row', gap: spacing.xs, marginTop: spacing.sm }}>
                    {(['present', 'absent', 'late'] as const).map((status) => (
                      <Pressable
                        key={status}
                        disabled={busy}
                        onPress={() => void mark(student.student_id, status, name)}
                        style={{
                          flex: 1,
                          paddingVertical: spacing.sm,
                          borderRadius: radius.sm,
                          borderWidth: 1,
                          borderColor: student.status === status ? palette.primary : palette.border,
                          backgroundColor:
                            student.status === status ? `${palette.primary}18` : palette.surfaceRaised,
                          opacity: busy ? 0.6 : 1,
                          alignItems: 'center',
                        }}
                      >
                        <Text
                          style={{
                            color: student.status === status ? palette.primary : palette.textSecondary,
                            fontWeight: '600',
                            fontSize: typography.caption.fontSize,
                            textTransform: 'capitalize',
                          }}
                        >
                          {status}
                        </Text>
                      </Pressable>
                    ))}
                  </View>
                </View>
              );
            })
          )}

          <Button
            label="Back to trip"
            variant="ghost"
            onPress={() => navigation.goBack()}
            style={{ marginTop: spacing.md }}
          />
        </>
      )}
    </ScreenContainer>
  );
};
