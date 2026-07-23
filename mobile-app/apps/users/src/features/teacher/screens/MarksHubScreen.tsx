import { useExams } from '@erp/core';
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
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const MarksHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const examsQuery = useExams({ per_page: 30, status: 'marking' });

  const exams = useMemo(
    () => examsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [examsQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={exams}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.md }}>
            <AcademicScreenHeader
              title="Marks entry"
              subtitle="Subjects you teach — bulk matrix or per-exam entry"
              onBack={() => navigation.goBack()}
            />
            <Pressable
              onPress={() => navigation.navigate('MarksMatrixSetup')}
              style={[
                styles.tile,
                {
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.md,
                },
              ]}
            >
              <Soft3DIcon name="grid-outline" tone="indigo" size={44} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Bulk marks matrix</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  Enter scores across subjects for a class and exam type
                </Text>
              </View>
            </Pressable>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '700',
                marginBottom: spacing.sm,
                fontSize: typography.body.fontSize,
              }}
            >
              Exams in marking
            </Text>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() =>
              navigation.navigate('MarksExamSetup', {
                examId: item.id,
                examName: item.name,
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
            <Soft3DIcon name="create-outline" tone="emerald" size={40} />
            <View style={{ flex: 1, marginLeft: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.name}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {[item.classroomName, item.subjectName].filter(Boolean).join(' · ') || 'Tap to enter marks'}
              </Text>
            </View>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={examsQuery.isRefetching && !examsQuery.isFetchingNextPage}
            onRefresh={() => void examsQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (examsQuery.hasNextPage && !examsQuery.isFetchingNextPage) void examsQuery.fetchNextPage();
        }}
        ListEmptyComponent={
          examsQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={4} />
          ) : examsQuery.isError ? (
            <EmptyState
              title="Could not load exams"
              message={(examsQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void examsQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No exams in marking"
              message="Use bulk matrix entry, or wait for exams to open for marking."
              icon="create-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  tile: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
});
