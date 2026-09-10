import { useDiaryThreads, type DiaryChannel } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ListRowCard,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useState } from 'react';
import { FlatList, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { goBackInStack } from '../../../navigation/navigateToTab';

type Nav = StackNavigationProp<ParentStackParamList>;

export const DiaryListScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { spacing } = useTheme();
  const [channel, setChannel] = useState<DiaryChannel>('teacher_parent');
  const threads = useDiaryThreads({ channel });

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="Messages"
          subtitle="Teacher and school conversations are separate"
          onBack={() => goBackInStack(navigation, 'ParentHome')}
        />
        <FilterChipRow label="Channel">
          <FilterChip
            label="Class teacher / admin"
            active={channel === 'teacher_parent'}
            onPress={() => setChannel('teacher_parent')}
          />
          <FilterChip
            label="Admin only"
            active={channel === 'admin_parent'}
            onPress={() => setChannel('admin_parent')}
          />
        </FilterChipRow>
      </View>

      {threads.isLoading ? (
        <SkeletonListRows count={5} />
      ) : threads.isError ? (
        <EmptyState
          title="Could not load conversations"
          message={threads.error instanceof Error ? threads.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void threads.refetch()}
        />
      ) : (threads.data ?? []).length === 0 ? (
        <EmptyState
          title={channel === 'admin_parent' ? 'No school conversations' : 'No teacher conversations'}
          message="Open a child hub to start a conversation in this channel."
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
              onPress={() =>
                navigation.navigate('DiaryChat', { studentId: item.student_id, channel })
              }
            />
          )}
        />
      )}
    </ScreenContainer>
  );
};
