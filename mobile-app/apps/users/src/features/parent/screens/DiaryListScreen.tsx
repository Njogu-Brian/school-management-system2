import { useDiaryThreads } from '@erp/core';
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
import { FlatList, Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatDateTime } from '../utils/format';

type Nav = StackNavigationProp<ParentStackParamList>;

export const DiaryListScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const threads = useDiaryThreads();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="Diary"
          subtitle="Messages with teachers"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />
      </View>

      {threads.isLoading ? (
        <SkeletonListRows count={5} />
      ) : threads.isError ? (
        <EmptyState
          title="Could not load diaries"
          message={threads.error instanceof Error ? threads.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (threads.data ?? []).length === 0 ? (
        <EmptyState
          title="No diary threads"
          message="Open a child and start a diary conversation from their hub."
          icon="chatbubbles-outline"
        />
      ) : (
        <FlatList
          data={threads.data ?? []}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('DiaryChat', { studentId: item.student_id })}
              style={{
                flexDirection: 'row',
                gap: spacing.md,
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Soft3DIcon name="chatbubbles-outline" tone="violet" size={44} />
              <View style={{ flex: 1 }}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>
                    {item.student_name ?? `Student #${item.student_id}`}
                  </Text>
                  {(item.unread_count ?? 0) > 0 ? (
                    <View
                      style={{
                        backgroundColor: colors.primary,
                        borderRadius: 10,
                        minWidth: 20,
                        paddingHorizontal: 6,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{ color: '#fff', fontSize: 12, fontWeight: '700' }}>{item.unread_count}</Text>
                    </View>
                  ) : null}
                </View>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {[item.admission_number, item.class_name].filter(Boolean).join(' · ')}
                </Text>
                <Text style={{ color: palette.textMuted, marginTop: spacing.xs }} numberOfLines={2}>
                  {item.latest_entry?.content ?? 'No messages yet'}
                </Text>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {formatDateTime(item.latest_entry?.created_at ?? item.updated_at)}
                </Text>
              </View>
            </Pressable>
          )}
        />
      )}
    </ScreenContainer>
  );
};
