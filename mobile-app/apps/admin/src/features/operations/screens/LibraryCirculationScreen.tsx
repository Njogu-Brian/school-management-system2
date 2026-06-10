import { useCan, useInfiniteBorrowings, useRenewBorrowing, useReturnBook } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';
import { formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'LibraryCirculation'>;

const STATUS_FILTERS = [
  { value: 'borrowed', label: 'Out' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'returned', label: 'Returned' },
  { value: 'all', label: 'All' },
] as const;

type StatusFilter = (typeof STATUS_FILTERS)[number]['value'];

export const LibraryCirculationScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, typography } = useTheme();
  const [status, setStatus] = useState<StatusFilter>('borrowed');
  const [search, setSearch] = useState('');

  const listQuery = useInfiniteBorrowings({
    enabled: canView,
    status,
    search: search.trim() || undefined,
  });
  const returnMutation = useReturnBook();
  const renewMutation = useRenewBorrowing();

  const items = useMemo(() => listQuery.data?.pages.flatMap((p) => p.items) ?? [], [listQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const onReturn = (id: number, title?: string | null) => {
    confirmAction('Return book', `Mark "${title ?? 'this book'}" as returned?`, 'Return', async () => {
      try {
        await returnMutation.mutateAsync({ id });
        showSuccess('Returned', 'Book checked back in.');
      } catch (err) {
        showError('Return failed', (err as Error).message);
      }
    });
  };

  const onRenew = (id: number, title?: string | null) => {
    confirmAction('Renew borrowing', `Extend "${title ?? 'this book'}" by 14 days?`, 'Renew', async () => {
      try {
        await renewMutation.mutateAsync({ id });
        showSuccess('Renewed', 'Due date extended.');
      } catch (err) {
        showError('Renew failed', (err as Error).message);
      }
    });
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={items}
        keyExtractor={(item) => String(item.id)}
        showFilterTrigger={false}
        hero={
          <View>
            <AcademicScreenHeader title="Circulation" subtitle="Issued books & returns" onBack={() => navigation.goBack()} />
            <View style={{ marginBottom: spacing.sm }}>
              <Button label="Issue a book" onPress={() => navigation.navigate('IssueBook')} />
            </View>
            <FilterChipRow>
              {STATUS_FILTERS.map((s) => (
                <FilterChip key={s.value} label={s.label} active={status === s.value} onPress={() => setStatus(s.value)} />
              ))}
            </FilterChipRow>
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search student or book…" />}
        renderItem={({ item }) => {
          const overdue = item.is_overdue;
          return (
            <View
              style={[
                styles.card,
                { backgroundColor: palette.surfaceRaised, borderColor: palette.borderSubtle },
              ]}
            >
              <View style={styles.cardHeader}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }} numberOfLines={2}>
                  {item.book_title ?? 'Book'}
                </Text>
                <StatusBadge
                  label={overdue ? 'Overdue' : item.status === 'borrowed' ? 'Out' : 'Returned'}
                  tone={overdue ? 'danger' : item.status === 'borrowed' ? 'warning' : 'success'}
                  compact
                />
              </View>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {[item.student_name, item.admission_number].filter(Boolean).join(' · ') || '—'}
              </Text>
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                Out {formatDateLabel(item.borrowed_date)} · Due {formatDateLabel(item.due_date)}
                {item.returned_date ? ` · Returned ${formatDateLabel(item.returned_date)}` : ''}
                {item.fine_amount ? ` · Fine KES ${item.fine_amount.toLocaleString()}` : ''}
              </Text>
              {item.can_return || item.can_renew ? (
                <View style={styles.actionsRow}>
                  {item.can_return ? (
                    <Pressable
                      onPress={() => onReturn(item.id, item.book_title)}
                      style={[styles.actionBtn, { backgroundColor: colors.primary }]}
                    >
                      <Text style={{ color: colors.white, fontWeight: '700', fontSize: typography.caption.fontSize }}>
                        Return
                      </Text>
                    </Pressable>
                  ) : null}
                  {item.can_renew ? (
                    <Pressable
                      onPress={() => onRenew(item.id, item.book_title)}
                      style={[styles.actionBtn, { borderWidth: 1, borderColor: colors.primary }]}
                    >
                      <Text style={{ color: colors.primary, fontWeight: '700', fontSize: typography.caption.fontSize }}>
                        Renew +14d
                      </Text>
                    </Pressable>
                  ) : null}
                </View>
              ) : null}
            </View>
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
        onEndReachedThreshold={0.4}
        ListFooterComponent={listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load circulation"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No borrowings"
              message={search ? 'Nothing matches your search.' : 'No books in this state.'}
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
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  actionsRow: { flexDirection: 'row', gap: 10, marginTop: 10 },
  actionBtn: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 10,
    alignItems: 'center',
  },
});
