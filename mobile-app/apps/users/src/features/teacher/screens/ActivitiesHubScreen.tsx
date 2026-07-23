import { useActivities, type ActivitySummary } from '@erp/core';
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
import { Pressable, RefreshControl, ScrollView, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const ActivitiesHubScreen: React.FC = () => {
  const { palette, spacing, typography, radius, colors } = useTheme();
  const navigation = useNavigation<Nav>();
  const activitiesQuery = useActivities();

  const activities = activitiesQuery.data ?? [];

  const open = (activity: ActivitySummary) => {
    navigation.navigate('ActivityAttendance', {
      activityId: activity.id,
      activityName: activity.name,
      activityType: activity.type,
    });
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      <View style={{ flex: 1, paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="Activities"
          subtitle="Swimming and extra-curricular attendance"
        />
        {activitiesQuery.isLoading ? (
          <SkeletonListRows variant="avatar" count={6} />
        ) : activities.length === 0 ? (
          <EmptyState
            title="No activities"
            message="No swimming classes or extra-curricular activities are available for you yet."
            icon="sparkles-outline"
          />
        ) : (
          <ScrollView
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{ paddingBottom: spacing.lg }}
            refreshControl={
              <RefreshControl
                refreshing={activitiesQuery.isFetching}
                onRefresh={() => void activitiesQuery.refetch()}
                tintColor={colors.primary}
              />
            }
          >
            {activities.map((activity) => (
              <Pressable
                key={activity.id}
                onPress={() => open(activity)}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  gap: spacing.md,
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderWidth: 1,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <Soft3DIcon
                  name={activity.type === 'swimming' ? 'water-outline' : 'sparkles-outline'}
                  tone={activity.type === 'swimming' ? 'cyan' : 'indigo'}
                  size={44}
                />
                <View style={{ flex: 1 }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{activity.name}</Text>
                  <Text
                    style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}
                  >
                    {activity.type === 'swimming' ? 'Swimming attendance' : 'Extra-curricular activity'}
                  </Text>
                </View>
                <Text style={{ color: colors.primary, fontSize: typography.caption.fontSize, fontWeight: '600' }}>
                  Mark
                </Text>
              </Pressable>
            ))}
          </ScrollView>
        )}
      </View>
    </ScreenContainer>
  );
};
