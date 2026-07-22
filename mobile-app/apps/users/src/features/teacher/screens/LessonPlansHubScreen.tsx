import { approvalsApi, type LessonPlanRecord } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useQuery } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const LessonPlansHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [filter, setFilter] = useState<'all' | 'draft' | 'submitted' | 'approved' | 'rejected'>('all');

  const plansQuery = useQuery({
    queryKey: ['lesson-plans', 'mine', filter] as const,
    queryFn: async () => {
      const res = await approvalsApi.listLessonPlans({
        submission_status: filter === 'all' ? undefined : filter,
        per_page: 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load lesson plans.');
      }
      return res.data.data ?? [];
    },
    staleTime: 30_000,
  });

  const plans = useMemo(() => plansQuery.data ?? [], [plansQuery.data]);

  const statusColor = (status?: string) => {
    switch (status) {
      case 'approved':
        return colors.success;
      case 'rejected':
        return colors.error;
      case 'submitted':
        return colors.warning;
      default:
        return palette.textMuted;
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={plans}
        keyExtractor={(item: LessonPlanRecord) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="Lesson plans"
              subtitle="Your authored plans"
              onBack={() => navigation.goBack()}
            />
            <Button
              label="Create lesson plan"
              onPress={() => navigation.navigate('CreateLessonPlan')}
              style={{ marginBottom: spacing.sm }}
            />
            <View style={styles.filters}>
              {(['all', 'draft', 'submitted', 'approved', 'rejected'] as const).map((f) => (
                <Pressable
                  key={f}
                  onPress={() => setFilter(f)}
                  style={[
                    styles.chip,
                    {
                      borderColor: filter === f ? colors.primary : palette.border,
                      backgroundColor: filter === f ? `${colors.primary}18` : palette.surface,
                      borderRadius: radius.full,
                    },
                  ]}
                >
                  <Text
                    style={{
                      color: filter === f ? colors.primary : palette.textSecondary,
                      fontSize: typography.caption.fontSize,
                      fontWeight: '600',
                      textTransform: 'capitalize',
                    }}
                  >
                    {f}
                  </Text>
                </Pressable>
              ))}
            </View>
          </View>
        }
        renderItem={({ item }) => {
          const status = item.submission_status ?? item.status ?? 'draft';
          return (
            <Pressable
              onPress={() => navigation.navigate('LessonPlanDetail', { lessonPlanId: item.id, topic: item.topic ?? undefined })}
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
              <Soft3DIcon name="document-text-outline" tone="indigo" size={40} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }} numberOfLines={2}>
                  {item.topic ?? `Lesson plan #${item.id}`}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[item.subject_name, item.class_name, item.date].filter(Boolean).join(' · ')}
                </Text>
              </View>
              <View style={[styles.badge, { backgroundColor: `${statusColor(status)}22` }]}>
                <Text style={{ color: statusColor(status), fontSize: 11, fontWeight: '700', textTransform: 'capitalize' }}>
                  {status}
                </Text>
              </View>
            </Pressable>
          );
        }}
        refreshControl={
          <RefreshControl
            refreshing={plansQuery.isRefetching}
            onRefresh={() => void plansQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        ListEmptyComponent={
          plansQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={4} />
          ) : plansQuery.isError ? (
            <EmptyState
              title="Could not load plans"
              message={(plansQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void plansQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No lesson plans"
              message="Create a draft for today or tomorrow, then submit it for review."
              icon="document-text-outline"
              actionLabel="Create"
              onAction={() => navigation.navigate('CreateLessonPlan')}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  filters: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 8 },
  chip: { borderWidth: 1, paddingHorizontal: 10, paddingVertical: 6 },
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8 },
});
