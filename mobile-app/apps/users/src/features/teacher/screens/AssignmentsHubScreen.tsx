import { useHomeworkDetail, useHomeworkList } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const AssignmentsHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();
  const listQuery = useHomeworkList();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="Homework" subtitle="Assignments for your classes" onBack={() => navigation.goBack()} />
        <Button
          label="Create homework"
          onPress={() => navigation.navigate('CreateAssignment')}
          style={{ marginBottom: spacing.sm }}
        />
      </View>
      {listQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : (listQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No homework yet"
          message="Create an assignment so parents and students can see it."
          icon="document-text-outline"
          actionLabel="Create"
          onAction={() => navigation.navigate('CreateAssignment')}
        />
      ) : (
        <FlatList
          data={listQuery.data ?? []}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('AssignmentDetail', { assignmentId: item.id })}
              style={{
                flexDirection: 'row',
                gap: spacing.md,
                alignItems: 'center',
                backgroundColor: palette.surface,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Soft3DIcon name="document-text-outline" tone="amber" size={40} />
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {[item.subject_name, item.class_name, item.due_date].filter(Boolean).join(' · ')}
                </Text>
              </View>
            </Pressable>
          )}
        />
      )}
    </ScreenContainer>
  );
};

export const AssignmentDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<TeacherStackParamList, 'AssignmentDetail'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const detail = useHomeworkDetail(route.params.assignmentId);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Homework detail" onBack={() => navigation.goBack()} />
      {detail.isLoading ? (
        <SkeletonListRows count={4} />
      ) : detail.isError || !detail.data ? (
        <EmptyState
          title="Could not load"
          message={detail.error instanceof Error ? detail.error.message : 'Try again.'}
          icon="alert-circle-outline"
        />
      ) : (
        <View
          style={{
            backgroundColor: palette.surface,
            borderWidth: 1,
            borderColor: palette.border,
            borderRadius: radius.lg,
            padding: spacing.md,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.headline.fontSize }}>
            {detail.data.title}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>
            {[detail.data.subject_name, detail.data.class_name, detail.data.stream_name, detail.data.due_date]
              .filter(Boolean)
              .join(' · ')}
          </Text>
          <Text style={{ color: palette.textPrimary, marginTop: spacing.md }}>
            {detail.data.instructions || 'No instructions provided.'}
          </Text>
        </View>
      )}
    </ScreenContainer>
  );
};
