import { useDiaryThreads } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ListRowCard,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { FlatList, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { goBackInStack } from '../../../navigation/navigateToTab';

type Nav = StackNavigationProp<ParentStackParamList>;

export const DiaryListScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { spacing } = useTheme();
  const threads = useDiaryThreads();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="Diary"
          subtitle="Messages with teachers"
          onBack={() => goBackInStack(navigation, 'MoreMenu')}
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
            <ListRowCard
              title={item.student_name ?? `Student #${item.student_id}`}
              subtitle={[item.admission_number, item.class_name].filter(Boolean).join(' · ')}
              meta={item.latest_entry?.content ?? 'No messages yet'}
              icon="chatbubbles-outline"
              glyph="chat"
              accent="info"
              badge={(item.unread_count ?? 0) > 0 ? String(item.unread_count) : undefined}
              badgeTone="brand"
              onPress={() => navigation.navigate('DiaryChat', { studentId: item.student_id })}
            />
          )}
        />
      )}
    </ScreenContainer>
  );
};
