import { useSpeedTests } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Pressable, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Nav = StackNavigationProp<AcademicsStackParamList>;

export const SpeedTestsScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();
  const listQuery = useSpeedTests();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Speed tests"
        subtitle="Short quizzes created by teachers — view marks"
        onBack={() => navigation.goBack()}
      />
      {listQuery.isLoading ? (
        <SkeletonListRows count={5} />
      ) : (listQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No speed tests"
          message="When teachers create speed tests, results appear here for office review."
          icon="flash-outline"
        />
      ) : (
        (listQuery.data ?? []).map((item) => (
          <Pressable
            key={item.batch_key}
            onPress={() =>
              navigation.navigate('SpeedTestDetail', { batchKey: item.batch_key, title: item.title })
            }
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
        ))
      )}
    </ScreenContainer>
  );
};
