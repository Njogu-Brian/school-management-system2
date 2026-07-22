import { useInfiniteRequirementsStudents } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const RequirementsHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [search, setSearch] = useState('');
  const listQuery = useInfiniteRequirementsStudents({ search: search.trim() || undefined });

  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={students}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="Requirements"
              subtitle="Class requirements collection"
              onBack={() => navigation.goBack()}
            />
            <TextField
              label="Search"
              value={search}
              onChangeText={setSearch}
              placeholder="Student name or admission #"
            />
          </View>
        }
        renderItem={({ item }) => {
          const disabled = !item.can_teacher_receive;
          return (
            <Pressable
              disabled={disabled}
              onPress={() => navigation.navigate('RequirementDetail', { studentId: item.id })}
              style={[
                styles.row,
                {
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                  opacity: disabled ? 0.55 : 1,
                },
              ]}
            >
              <Soft3DIcon name="clipboard-outline" tone="amber" size={40} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.full_name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[item.admission_number, item.class_name, item.stream_name].filter(Boolean).join(' · ')}
                </Text>
                {item.is_new_joiner ? (
                  <Text style={{ color: colors.warning, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                    New joiner
                  </Text>
                ) : null}
                {disabled ? (
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                    Admin only (new-joiner requirements)
                  </Text>
                ) : null}
              </View>
            </Pressable>
          );
        }}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) void listQuery.fetchNextPage();
        }}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={5} />
          ) : listQuery.isError ? (
            <EmptyState
              title="Could not load students"
              message={(listQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No students"
              message="No students available for requirements collection."
              icon="clipboard-outline"
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
