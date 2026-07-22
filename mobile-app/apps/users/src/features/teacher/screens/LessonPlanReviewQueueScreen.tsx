import { useModerationQueue } from '@erp/core';
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
import React, { useMemo } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const LessonPlanReviewQueueScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const queueQuery = useModerationQueue({ per_page: 25 });

  const items = useMemo(
    () => queueQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [queueQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <AcademicScreenHeader
            title="Lesson plan review"
            subtitle="Submitted plans awaiting approval"
            onBack={() => navigation.goBack()}
          />
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() =>
              navigation.navigate('LessonPlanReviewDetail', {
                lessonPlanId: item.id,
                topic: item.topic,
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
            <Soft3DIcon name="checkmark-circle-outline" tone="emerald" size={40} />
            <View style={{ flex: 1, marginLeft: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }} numberOfLines={2}>
                {item.topic || `Plan #${item.id}`}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {[item.teacherName, item.className, item.subjectName, item.plannedDate].filter(Boolean).join(' · ')}
              </Text>
            </View>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={queueQuery.isRefetching && !queueQuery.isFetchingNextPage}
            onRefresh={() => void queueQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (queueQuery.hasNextPage && !queueQuery.isFetchingNextPage) void queueQuery.fetchNextPage();
        }}
        ListFooterComponent={
          queueQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null
        }
        ListEmptyComponent={
          queueQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={4} />
          ) : queueQuery.isError ? (
            <EmptyState
              title="Could not load queue"
              message={(queueQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void queueQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="Queue empty"
              message="No lesson plans waiting for review."
              icon="checkmark-circle-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
});
