import { useStudentDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';

type DetailParams = { studentId: number };

export const StudentDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const { palette, spacing, typography } = useTheme();
  const studentId = (route.params as DetailParams | undefined)?.studentId ?? 0;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

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
            {detail.data?.fullName ?? (detail.isLoading ? 'Loading…' : `Student #${studentId}`)}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>
            {[detail.data?.admissionNumber, detail.data?.className, detail.data?.streamName]
              .filter(Boolean)
              .join(' · ') || 'Scoped student profile'}
          </Text>
        </View>
      )}
    </ScreenContainer>
  );
};
