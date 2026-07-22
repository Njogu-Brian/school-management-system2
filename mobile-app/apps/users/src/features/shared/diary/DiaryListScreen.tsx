import { useDiaryThreads } from '@erp/core';
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
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';

type DiaryNav = StackNavigationProp<{
  DiaryList: undefined;
  DiaryChat: { studentId: number; studentName?: string };
}>;

export const DiaryListScreen: React.FC = () => {
  const navigation = useNavigation<DiaryNav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [search, setSearch] = useState('');
  const threadsQuery = useDiaryThreads({ search: search.trim() || undefined });

  const threads = useMemo(() => threadsQuery.data ?? [], [threadsQuery.data]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={threads}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="Student diary"
              subtitle="Parent–teacher message threads"
              onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
            />
            <TextField
              label="Search"
              value={search}
              onChangeText={setSearch}
              placeholder="Student name or admission #"
            />
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() =>
              navigation.navigate('DiaryChat', {
                studentId: item.student_id,
                studentName: item.student_name ?? undefined,
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
            <Soft3DIcon name="chatbubbles-outline" tone="indigo" size={40} />
            <View style={{ flex: 1, marginLeft: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                {item.student_name ?? `Student #${item.student_id}`}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }} numberOfLines={1}>
                {[item.class_name, item.admission_number].filter(Boolean).join(' · ') || 'No class info'}
              </Text>
              {item.latest_entry?.content ? (
                <Text
                  style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}
                  numberOfLines={2}
                >
                  {item.latest_entry.content}
                </Text>
              ) : null}
            </View>
            {(item.unread_count ?? 0) > 0 ? (
              <View style={[styles.badge, { backgroundColor: colors.primary }]}>
                <Text style={{ color: '#fff', fontSize: 11, fontWeight: '700' }}>{item.unread_count}</Text>
              </View>
            ) : null}
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={threadsQuery.isRefetching}
            onRefresh={() => void threadsQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        ListEmptyComponent={
          threadsQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={5} />
          ) : threadsQuery.isError ? (
            <EmptyState
              title="Could not load diaries"
              message={(threadsQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void threadsQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No diary threads"
              message="Conversations with parents will appear here."
              icon="chatbubbles-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
  badge: {
    minWidth: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
  },
});
