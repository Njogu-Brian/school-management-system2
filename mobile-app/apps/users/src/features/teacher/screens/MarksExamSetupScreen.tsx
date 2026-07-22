import { useExamMarkingOptions } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;
type Route = RouteProp<TeacherStackParamList, 'MarksExamSetup'>;

export const MarksExamSetupScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const { examId, examName } = route.params;
  const { palette, spacing, typography, radius } = useTheme();
  const optionsQuery = useExamMarkingOptions(examId);

  const options = optionsQuery.data ?? [];

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Choose class & subject"
        subtitle={examName}
        onBack={() => navigation.goBack()}
      />
      {optionsQuery.isLoading ? (
        <SkeletonListRows variant="compact" count={3} />
      ) : optionsQuery.isError ? (
        <EmptyState
          title="Could not load options"
          message={(optionsQuery.error as Error)?.message ?? 'Something went wrong.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void optionsQuery.refetch()}
        />
      ) : options.length === 0 ? (
        <EmptyState
          title="No marking options"
          message="This exam has no class/subject combinations available for marking."
          icon="create-outline"
        />
      ) : (
        options.map((opt) => (
          <Pressable
            key={`${opt.classroom_id}-${opt.subject_id}`}
            onPress={() =>
              navigation.navigate('MarksEntry', {
                examId,
                classroomId: opt.classroom_id,
                subjectId: opt.subject_id,
                classroomName: opt.classroom_name,
                subjectName: opt.subject_name,
              })
            }
            style={[
              styles.row,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              },
            ]}
          >
            <Soft3DIcon name="school-outline" tone="cyan" size={40} />
            <View style={{ flex: 1, marginLeft: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{opt.classroom_name}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {opt.subject_name}
              </Text>
            </View>
          </Pressable>
        ))
      )}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
});
