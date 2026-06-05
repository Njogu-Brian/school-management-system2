import {
  useInfiniteGlobalSearch,
  useNetworkStatus,
  useOfflineSearch,
  useSearchSuggestions,
  type SearchModuleFilter,
} from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import AsyncStorage from '@react-native-async-storage/async-storage';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { resolveSearchRoute } from '../resolveSearchRoute';

type Props = StackScreenProps<DashboardStackParamList, 'GlobalSearch'>;

const HISTORY_KEY = 'admin_global_search_history';
const MODULE_FILTERS: { id: SearchModuleFilter; label: string }[] = [
  { id: 'all', label: 'All' },
  { id: 'students', label: 'Students' },
  { id: 'staff', label: 'Staff' },
  { id: 'finance', label: 'Finance' },
  { id: 'operations', label: 'Operations' },
  { id: 'communication', label: 'Communication' },
];

function useDebouncedValue<T>(value: T, delayMs: number): T {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delayMs);
    return () => clearTimeout(t);
  }, [value, delayMs]);
  return debounced;
}

export const GlobalSearchScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const network = useNetworkStatus();
  const isOffline = network === 'offline';

  const [query, setQuery] = useState('');
  const [moduleFilter, setModuleFilter] = useState<SearchModuleFilter>('all');
  const [history, setHistory] = useState<string[]>([]);
  const debouncedQuery = useDebouncedValue(query, 350);

  const searchQuery = useInfiniteGlobalSearch({
    query: debouncedQuery,
    module: moduleFilter,
    enabled: !isOffline,
  });
  const suggestionsQuery = useSearchSuggestions(debouncedQuery, !isOffline && debouncedQuery.length < 2);
  const offlineHits = useOfflineSearch(isOffline ? debouncedQuery : '');

  useEffect(() => {
    void AsyncStorage.getItem(HISTORY_KEY).then((raw) => {
      if (raw) setHistory(JSON.parse(raw) as string[]);
    });
  }, []);

  const saveHistory = useCallback(async (term: string) => {
    setHistory((prev) => {
      const next = [term, ...prev.filter((h) => h !== term)].slice(0, 10);
      void AsyncStorage.setItem(HISTORY_KEY, JSON.stringify(next));
      return next;
    });
  }, []);

  const onlineItems = useMemo(
    () => searchQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [searchQuery.data],
  );
  const items = isOffline ? offlineHits : onlineItems;
  const showSuggestions = debouncedQuery.length >= 1 && debouncedQuery.length < 2 && !isOffline;

  const onSelect = useCallback(
    (hit: (typeof items)[number]) => {
      void saveHistory(debouncedQuery || hit.title);
      resolveSearchRoute(navigation, hit);
    },
    [navigation, debouncedQuery, saveHistory],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={showSuggestions ? [] : items}
        keyExtractor={(item) => item.id}
        contentContainerStyle={{ padding: spacing.md }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="Search" onBack={() => navigation.goBack()} />
            {isOffline ? (
              <Text style={{ color: colors.warning, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
                Offline — searching cached data
              </Text>
            ) : null}
            <TextInput
              value={query}
              onChangeText={setQuery}
              placeholder="Search students, staff, finance, operations…"
              placeholderTextColor={palette.textSecondary}
              autoFocus
              style={[styles.input, { borderColor: palette.border, color: palette.textPrimary }]}
            />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: spacing.sm }}>
              {MODULE_FILTERS.map((m) => (
                <Pressable
                  key={m.id}
                  onPress={() => setModuleFilter(m.id)}
                  style={[styles.chip, moduleFilter === m.id && { borderColor: colors.primary }]}
                >
                  <Text style={{ fontSize: fontSizes.xs }}>{m.label}</Text>
                </Pressable>
              ))}
            </View>
            {history.length > 0 && debouncedQuery.length < 2 ? (
              <View style={{ marginBottom: spacing.sm }}>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 4 }}>Recent</Text>
                <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6 }}>
                  {history.map((h) => (
                    <Pressable key={h} onPress={() => setQuery(h)} style={styles.chip}>
                      <Text style={{ fontSize: fontSizes.xs }}>{h}</Text>
                    </Pressable>
                  ))}
                </View>
              </View>
            ) : null}
            {showSuggestions && (suggestionsQuery.data?.length ?? 0) > 0 ? (
              <View style={{ marginBottom: spacing.sm }}>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 4 }}>Suggestions</Text>
                {suggestionsQuery.data?.map((s) => (
                  <Pressable key={s.id} onPress={() => setQuery(s.title)} style={[styles.row, { borderColor: palette.border }]}>
                    <Text style={{ color: palette.textPrimary }}>{s.title}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{s.module}</Text>
                  </Pressable>
                ))}
              </View>
            ) : null}
            {searchQuery.isFetching && !searchQuery.isFetchingNextPage ? (
              <ActivityIndicator color={colors.primary} style={{ marginBottom: spacing.sm }} />
            ) : null}
          </>
        }
        renderItem={({ item }) => (
          <Pressable onPress={() => onSelect(item)} style={[styles.row, { borderColor: palette.border }]}>
            <Text style={{ fontWeight: '600', color: palette.textPrimary }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
              {[item.module, item.subtitle].filter(Boolean).join(' · ')}
            </Text>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={searchQuery.isRefetching && !searchQuery.isFetchingNextPage}
            onRefresh={() => void searchQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (!isOffline && searchQuery.hasNextPage && !searchQuery.isFetchingNextPage) {
            void searchQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={searchQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
        ListEmptyComponent={
          !searchQuery.isLoading && debouncedQuery.length >= 2 ? (
            <EmptyState title="No results" message="Try a different term or filter." icon="search-outline" />
          ) : null
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  input: { borderWidth: 1, borderRadius: 8, padding: 12, marginBottom: 12 },
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 14, paddingHorizontal: 8, paddingVertical: 4 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
