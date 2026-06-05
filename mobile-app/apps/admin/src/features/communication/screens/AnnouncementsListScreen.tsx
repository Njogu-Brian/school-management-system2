import { useCan, useInfiniteAnnouncements } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<CommunicationStackParamList, 'AnnouncementsList'>;

export const AnnouncementsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const listQuery = useInfiniteAnnouncements({ enabled: canView });

  const items = useMemo(() => listQuery.data?.pages.flatMap((p) => p.items) ?? [], [listQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader title="Announcements" onBack={() => navigation.goBack()} />
            <Pressable onPress={() => navigation.navigate('AnnouncementForm')} style={{ marginBottom: spacing.sm }}>
              <Text style={{ color: colors.primary, fontWeight: '600' }}>+ New announcement</Text>
            </Pressable>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('AnnouncementDetail', { announcementId: item.id })}
            style={[styles.card, { borderColor: palette.border }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.md }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: spacing.xs }} numberOfLines={2}>
              {item.content}
            </Text>
            {item.expires_at ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.xs }}>
                Expires {formatDateLabel(item.expires_at)}
              </Text>
            ) : null}
          </Pressable>
        )}
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
        onEndReachedThreshold={0.4}
        ListFooterComponent={listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
        ListEmptyComponent={
          listQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : listQuery.isError ? (
            <Pressable onPress={() => void listQuery.refetch()}>
              <Text style={{ color: colors.error, textAlign: 'center' }}>Retry</Text>
            </Pressable>
          ) : (
            <EmptyState title="No announcements" message="Create your first announcement." icon="megaphone-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 16, marginBottom: 8 },
});
