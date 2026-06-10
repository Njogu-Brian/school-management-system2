import { useCan, useInfiniteLibraryBooks } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, RefreshControl, StyleSheet, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { capitalizeStatus } from '../../shared/utils/formatters';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'LibraryBooks'>;

export const LibraryBooksScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette } = useTheme();
  const [search, setSearch] = useState('');

  const listQuery = useInfiniteLibraryBooks({
    enabled: canView,
    search: search.trim() || undefined,
  });

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
      <RegistryListLayout
        data={items}
        keyExtractor={(item) => String(item.id)}
        showFilterTrigger={false}
        hero={
          <AcademicScreenHeader title="Library" subtitle="Book catalogue" onBack={() => navigation.goBack()} />
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search title, author or ISBN…" />}
        renderItem={({ item }) => (
          <OpsListCard
            title={item.title || 'Untitled'}
            lines={[item.author, item.isbn ? `ISBN ${item.isbn}` : null]}
            badge={{
              label: capitalizeStatus(item.status),
              tone: item.status === 'available' ? 'success' : 'warning',
            }}
          />
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
              title="Could not load books"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No books"
              message={search ? 'No books match your search.' : 'The library catalogue is empty.'}
              icon="library-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
