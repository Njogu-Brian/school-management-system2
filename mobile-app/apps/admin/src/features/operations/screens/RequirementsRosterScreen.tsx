import { useCan, useInfiniteRequirementsStudents } from '@erp/core';
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
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'RequirementsRoster'>;

export const RequirementsRosterScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette } = useTheme();
  const [search, setSearch] = useState('');

  const listQuery = useInfiniteRequirementsStudents({
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
          <AcademicScreenHeader
            title="Student requirements"
            subtitle="Term requirements checklist by student"
            onBack={() => navigation.goBack()}
          />
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search name or admission no…" />}
        renderItem={({ item }) => (
          <OpsListCard
            title={item.full_name}
            lines={[
              [item.admission_number, item.class_name, item.stream_name].filter(Boolean).join(' · '),
            ]}
            badge={item.is_new_joiner ? { label: 'New joiner', tone: 'info' } : undefined}
            onPress={() =>
              navigation.navigate('RequirementsStudent', {
                studentId: item.id,
                studentName: item.full_name,
              })
            }
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
            <SkeletonListRows variant="avatar" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load roster"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No students"
              message={search ? 'No students match your search.' : 'No students found.'}
              icon="checkbox-outline"
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
