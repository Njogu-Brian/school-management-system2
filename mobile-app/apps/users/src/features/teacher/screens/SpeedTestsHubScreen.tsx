import { useSpeedTests } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useListRefreshControl,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const SpeedTestsHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const listQuery = useSpeedTests();
  const refreshControl = useListRefreshControl(colors.primary);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="Speed tests"
          subtitle="Short subject quizzes — set questions and enter marks"
          onBack={() => navigation.goBack()}
        />
        <Button
          label="Create speed test"
          onPress={() => navigation.navigate('CreateSpeedTest')}
          style={{ marginBottom: spacing.sm }}
        />
      </View>
      {listQuery.isLoading ? (
        <SkeletonListRows count={5} />
      ) : (listQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No speed tests yet"
          message="Create a 5-question maths quiz or a longer CRE test, then enter marks."
          icon="flash-outline"
          actionLabel="Create"
          onAction={() => navigation.navigate('CreateSpeedTest')}
        />
      ) : (
        <FlatList
          data={listQuery.data ?? []}
          keyExtractor={(item) => item.batch_key}
          refreshControl={refreshControl}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('SpeedTestMarks', { batchKey: item.batch_key, title: item.title })}
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
              <Soft3DIcon name="flash-outline" tone="amber" size={40} />
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {[item.subject_name, item.classroom_name, item.question_count ? `${item.question_count} questions` : null]
                    .filter(Boolean)
                    .join(' · ')}
                </Text>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  Marked {item.marked_count}/{item.student_count}
                  {item.max_marks != null ? ` · out of ${item.max_marks}` : ''}
                </Text>
              </View>
            </Pressable>
          )}
        />
      )}
    </ScreenContainer>
  );
};
