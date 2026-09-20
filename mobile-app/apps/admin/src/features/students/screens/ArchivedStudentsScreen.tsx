import { useCan, useInfiniteArchivedStudents } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StudentSearchBar,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';

type Props = StackScreenProps<StudentsStackParamList, 'ArchivedStudents'>;

function formatExitDate(value?: string | null): string | null {
  if (!value) return null;
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value.slice(0, 10);
  return d.toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' });
}

export const ArchivedStudentsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('students.view');
  const { palette, colors, spacing, typography, radius } = useTheme();
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [termId, setTermId] = useState<number | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput.trim()), 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const listQuery = useInfiniteArchivedStudents(
    { search: search || undefined, termId },
    { enabled: canView },
  );

  const rows = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.data) ?? [],
    [listQuery.data],
  );
  const total = listQuery.data?.pages[0]?.total;
  const terms = listQuery.data?.pages[0]?.available_terms ?? [];

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need students.view permission to open archived students."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      scrollProps={{
        refreshControl: (
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
          />
        ),
      }}
    >
      <AcademicScreenHeader
        title="Archived students"
        subtitle={total != null ? `${total} archived` : 'Who left, and in which term'}
        onBack={() => navigation.goBack()}
      />

      <StudentSearchBar
        value={searchInput}
        onChangeText={setSearchInput}
        placeholder="Search archived students…"
      />

      <FilterChipRow label="Term archived" wrap>
        <FilterChip label="All terms" active={termId == null} onPress={() => setTermId(null)} />
        {terms.map((term) => (
          <FilterChip
            key={term.id}
            label={term.academic_year ? `${term.name} · ${term.academic_year}` : term.name}
            active={termId === term.id}
            onPress={() => setTermId(term.id)}
          />
        ))}
      </FilterChipRow>

      {listQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : listQuery.isError ? (
        <EmptyState
          title="Could not load archived students"
          message={(listQuery.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void listQuery.refetch()}
        />
      ) : rows.length === 0 ? (
        <ListEmptyState
          title="No archived students"
          message="Nobody matches this term or search."
          icon="archive-outline"
        />
      ) : (
        rows.map((row) => {
          const exit = formatExitDate(row.transfer_date ?? row.archived_at);
          const termLabel = row.term
            ? row.term.academic_year
              ? `${row.term.name} · ${row.term.academic_year}`
              : row.term.name
            : 'Term not recorded';
          return (
            <Pressable
              key={row.id}
              onPress={() => navigation.navigate('StudentDetail', { studentId: row.id })}
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{row.full_name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {row.admission_number}
                  {row.class_name ? ` · ${row.class_name}` : ''}
                  {row.stream_name ? ` ${row.stream_name}` : ''}
                </Text>
                <Text style={{ color: palette.textPrimary, marginTop: spacing.xs, fontWeight: '600' }}>
                  Archived in {termLabel}
                </Text>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {exit ? `Left ${exit}` : 'Exit date not recorded'}
                  {row.archived_reason ? ` · ${row.archived_reason}` : ''}
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={16} color={palette.textMuted} />
            </Pressable>
          );
        })
      )}

      {listQuery.hasNextPage ? (
        <Pressable
          onPress={() => void listQuery.fetchNextPage()}
          style={{ alignItems: 'center', paddingVertical: spacing.md }}
        >
          {listQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: colors.primary, fontWeight: '700' }}>Load more</Text>
          )}
        </Pressable>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flexGrow: 1, justifyContent: 'center' },
});
