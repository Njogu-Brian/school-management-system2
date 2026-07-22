import { useInfiniteAnnouncements } from '@erp/core';
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
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';

export const AnnouncementsListScreen: React.FC = () => {
  const navigation = useNavigation();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [search, setSearch] = useState('');
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const listQuery = useInfiniteAnnouncements({ perPage: 25 });

  const items = useMemo(() => {
    const all = listQuery.data?.pages.flatMap((p) => p.items) ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return all;
    return all.filter(
      (a) => a.title.toLowerCase().includes(q) || (a.content ?? '').toLowerCase().includes(q),
    );
  }, [listQuery.data, search]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="Announcements"
              subtitle="School notices"
              onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
            />
            <TextField
              label="Search"
              value={search}
              onChangeText={setSearch}
              placeholder="Search announcements…"
            />
          </View>
        }
        renderItem={({ item }) => {
          const open = expandedId === item.id;
          return (
            <Pressable
              onPress={() => setExpandedId(open ? null : item.id)}
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
              <Soft3DIcon name="megaphone-outline" tone="amber" size={40} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
                <Text
                  style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}
                  numberOfLines={open ? undefined : 2}
                >
                  {item.content}
                </Text>
                {item.expires_at ? (
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    Expires {String(item.expires_at).slice(0, 10)}
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
            <SkeletonListRows variant="compact" count={4} />
          ) : listQuery.isError ? (
            <EmptyState
              title="Could not load announcements"
              message={(listQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No announcements"
              message="Published school announcements will appear here."
              icon="megaphone-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'flex-start', borderWidth: StyleSheet.hairlineWidth },
});
