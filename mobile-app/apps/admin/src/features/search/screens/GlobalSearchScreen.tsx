import {
  useInfiniteGlobalSearch,
  useNetworkStatus,
  useOfflineSearch,
  useSearchSuggestions,
  type SearchHit,
  type SearchModuleFilter,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
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
  View,
} from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { resolveSearchRoute } from '../resolveSearchRoute';
import { searchMenuItems } from '../searchMenuItems';
import { navigateDashboardBack } from '../../../navigation/navigateWorkspace';

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

const MODULE_LABELS: Record<string, string> = {
  students: 'Students',
  staff: 'Staff',
  finance: 'Finance',
  admissions: 'Admissions',
  operations: 'Operations',
  communication: 'Communication',
  academics: 'Academics',
  Menu: 'Menu',
};

type SearchRow =
  | { kind: 'header'; id: string; module: string }
  | { kind: 'hit'; id: string; item: SearchHit };

function useDebouncedValue<T>(value: T, delayMs: number): T {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delayMs);
    return () => clearTimeout(t);
  }, [value, delayMs]);
  return debounced;
}

export const GlobalSearchScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
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

  const onlineItems = useMemo(() => {
    const apiItems = searchQuery.data?.pages.flatMap((p) => p.items) ?? [];
    if (moduleFilter !== 'all' || debouncedQuery.length < 2) {
      return apiItems;
    }
    const menuHits = searchMenuItems(debouncedQuery);
    const seen = new Set(apiItems.map((h) => h.id));
    const merged = [...apiItems];
    for (const hit of menuHits) {
      if (!seen.has(hit.id)) merged.push(hit);
    }
    return merged;
  }, [searchQuery.data, moduleFilter, debouncedQuery]);
  const items = isOffline ? offlineHits : onlineItems;
  const showSuggestions = debouncedQuery.length >= 1 && debouncedQuery.length < 2 && !isOffline;

  const groupedRows = useMemo((): SearchRow[] => {
    if (showSuggestions) return [];
    const grouped = new Map<string, typeof items>();
    for (const item of items) {
      const mod = MODULE_LABELS[item.module ?? ''] ?? item.module ?? 'Results';
      if (!grouped.has(mod)) grouped.set(mod, []);
      grouped.get(mod)!.push(item);
    }
    const out: SearchRow[] = [];
    for (const [module, hits] of grouped) {
      out.push({ kind: 'header', id: `header-${module}`, module });
      for (const hit of hits) out.push({ kind: 'hit', id: hit.id, item: hit });
    }
    return out;
  }, [items, showSuggestions]);

  const onSelect = useCallback(
    (hit: (typeof items)[number]) => {
      void saveHistory(debouncedQuery || hit.title);
      resolveSearchRoute(navigation, hit);
    },
    [navigation, debouncedQuery, saveHistory],
  );

  const rowStyle = {
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: radius.card,
    padding: spacing.mdSm,
    marginBottom: spacing.sm,
  };

  const listHeader = (
    <View style={{ paddingHorizontal: spacing.md, paddingBottom: spacing.sm }}>
      <AcademicScreenHeader
        title="Global search"
        subtitle="One search across your school"
        onBack={() => navigateDashboardBack(navigation)}
      />
      {isOffline ? (
        <Text
          style={{
            color: colors.warning,
            fontSize: typography.caption.fontSize,
            marginBottom: spacing.sm,
          }}
        >
          Offline — searching cached data
        </Text>
      ) : null}

      {history.length > 0 && debouncedQuery.length < 2 ? (
        <View style={{ marginBottom: spacing.sm }}>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
            }}
          >
            Recent
          </Text>
          <FilterChipRow>
            {history.map((h) => (
              <FilterChip key={h} label={h} active={false} onPress={() => setQuery(h)} />
            ))}
          </FilterChipRow>
        </View>
      ) : null}

      {showSuggestions && (suggestionsQuery.data?.length ?? 0) > 0 ? (
        <View style={{ marginBottom: spacing.sm }}>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
            }}
          >
            Suggestions
          </Text>
          {suggestionsQuery.data?.map((s) => (
            <Pressable
              key={s.id}
              onPress={() => setQuery(s.title)}
              style={[rowStyle, { borderColor: palette.borderSubtle, backgroundColor: palette.surfaceRaised }]}
            >
              <Text style={{ color: palette.textPrimary, fontSize: typography.body.fontSize }}>{s.title}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{s.module}</Text>
            </Pressable>
          ))}
        </View>
      ) : null}
    </View>
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View
        style={[
          styles.sticky,
          {
            paddingHorizontal: spacing.md,
            paddingTop: spacing.sm,
            paddingBottom: spacing.sm,
            backgroundColor: palette.surface,
            borderBottomColor: palette.borderSubtle,
            gap: spacing.sm,
          },
        ]}
      >
        <SearchBar
          value={query}
          onChangeText={setQuery}
          placeholder="Search anything…"
          autoFocus
        />
        <FilterChipRow>
          {MODULE_FILTERS.map((m) => (
            <FilterChip
              key={m.id}
              label={m.label}
              active={moduleFilter === m.id}
              onPress={() => setModuleFilter(m.id)}
            />
          ))}
        </FilterChipRow>
      </View>

      <FlatList
        data={groupedRows}
        keyExtractor={(row) => row.id}
        ListHeaderComponent={listHeader}
        contentContainerStyle={{ paddingBottom: spacing.xl }}
        renderItem={({ item: row }) => {
          if (row.kind === 'header') {
            return (
              <Text
                style={{
                  color: palette.textMuted,
                  fontSize: typography.overline.fontSize,
                  fontWeight: typography.overline.fontWeight,
                  letterSpacing: typography.overline.letterSpacing,
                  textTransform: 'uppercase',
                  marginTop: spacing.md,
                  marginBottom: spacing.xs,
                  paddingHorizontal: spacing.md,
                }}
              >
                {row.module}
              </Text>
            );
          }
          const hit = row.item;
          return (
            <Pressable
              onPress={() => onSelect(hit)}
              style={[
                rowStyle,
                {
                  marginHorizontal: spacing.md,
                  borderColor: palette.borderSubtle,
                  backgroundColor: palette.surfaceRaised,
                },
              ]}
            >
              <Text
                style={{
                  fontWeight: typography.titleSmall.fontWeight,
                  color: palette.textPrimary,
                  fontSize: typography.titleSmall.fontSize,
                }}
              >
                {hit.title}
              </Text>
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                  marginTop: spacing.xs / 2,
                }}
              >
                {hit.subtitle ?? MODULE_LABELS[hit.module ?? ''] ?? hit.module}
              </Text>
            </Pressable>
          );
        }}
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
        ListFooterComponent={
          searchQuery.isFetching && !searchQuery.isFetchingNextPage ? (
            <SkeletonListRows count={3} variant="compact" />
          ) : searchQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          searchQuery.isLoading && debouncedQuery.length >= 2 ? (
            <SkeletonListRows count={5} variant="compact" />
          ) : searchQuery.isError && debouncedQuery.length >= 2 ? (
            <EmptyState
              title="Search failed"
              message={(searchQuery.error as Error)?.message ?? 'Could not load results.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void searchQuery.refetch()}
            />
          ) : !searchQuery.isLoading && debouncedQuery.length >= 2 ? (
            <EmptyState title="No results" message="Try a different term or filter." icon="search-outline" />
          ) : null
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  sticky: { zIndex: 2, borderBottomWidth: StyleSheet.hairlineWidth },
});
