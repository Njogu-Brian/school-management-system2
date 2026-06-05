import { useCan, useModerationQueue } from '@erp/core';
import {
  AcademicScreenHeader,
  AcademicSearchBar,
  ModerationCard,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { useModerationRegistryState } from '../hooks/useModerationRegistryState';

type Props = StackScreenProps<AcademicsStackParamList, 'Moderation'>;

export const ModerationScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view') && useCan('lesson_plans.view');
  const { colors, palette, spacing } = useTheme();
  const { searchInput, setSearchInput, filters } = useModerationRegistryState();
  const queueQuery = useModerationQueue(filters, { enabled: canView });

  const items = queueQuery.data?.pages.flatMap((p) => p.items) ?? [];

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={queueQuery.isRefetching && !queueQuery.isFetchingNextPage}
            onRefresh={() => void queueQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListHeaderComponent={
          <>
            <AcademicScreenHeader
              title="Moderation"
              subtitle="Lesson plan review queue"
              onBack={() => navigation.goBack()}
            />
            <AcademicSearchBar
              value={searchInput}
              onChangeText={setSearchInput}
              placeholder="Search topic, teacher, class…"
            />
          </>
        }
        renderItem={({ item }) => (
          <ModerationCard
            data={{
              plan: item,
              onPress: () =>
                navigation.navigate('LessonPlanReview', {
                  lessonPlanId: item.id,
                  summary: item,
                }),
            }}
          />
        )}
        ListEmptyComponent={
          queueQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : queueQuery.isError ? (
            <Pressable onPress={() => void queueQuery.refetch()}>
              <Text style={{ color: colors.error }}>{(queueQuery.error as Error).message}</Text>
            </Pressable>
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>
              No lesson plans pending review.
            </Text>
          )
        }
        onEndReached={() => {
          if (queueQuery.hasNextPage && !queueQuery.isFetchingNextPage) {
            void queueQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.3}
        ListFooterComponent={
          queueQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} />
          ) : null
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
