import { useCan, useInfiniteAnnouncements } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
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
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const [search, setSearch] = useState('');
  const listQuery = useInfiniteAnnouncements({ enabled: canView });

  const items = useMemo(() => {
    const all = listQuery.data?.pages.flatMap((p) => p.items) ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return all;
    return all.filter(
      (a) => a.title.toLowerCase().includes(q) || a.content.toLowerCase().includes(q),
    );
  }, [listQuery.data, search]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={items}
        keyExtractor={(item) => String(item.id)}
        showFilterTrigger={false}
        hero={
          <View>
            <AcademicScreenHeader title="Announcements" onBack={() => navigation.goBack()} />
            <Pressable
              onPress={() => navigation.navigate('AnnouncementForm')}
              style={({ pressed }) => [
                styles.newBtn,
                {
                  backgroundColor: colors.primary,
                  borderRadius: radius.md,
                  paddingVertical: spacing.sm,
                  paddingHorizontal: spacing.md,
                  opacity: pressed ? 0.85 : 1,
                },
              ]}
            >
              <Ionicons name="add" size={18} color={colors.white} />
              <Text style={{ color: colors.white, fontWeight: '700' }}>New announcement</Text>
            </Pressable>
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search announcements…" />}
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('AnnouncementDetail', { announcementId: item.id })}
            style={({ pressed }) => [
              elevation[1],
              {
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: palette.borderSubtle,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
              {item.title}
            </Text>
            <Text
              style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}
              numberOfLines={2}
            >
              {item.content}
            </Text>
            {item.expires_at ? (
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
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
            <SkeletonListRows variant="card" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load announcements"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No announcements"
              message={search ? 'No announcements match your search.' : 'Create your first announcement.'}
              icon="megaphone-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  newBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 6,
    marginBottom: 4,
  },
});
