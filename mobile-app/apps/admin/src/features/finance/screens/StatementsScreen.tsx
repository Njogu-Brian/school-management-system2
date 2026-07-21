import { useCan, useInfiniteStudentList, useStudentStatement, useAcademicYearsSettings, useTermsSettings } from '@erp/core';
import {
  EmptyState,
  FinanceScreenHeader,
  FinanceSearchBar,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatementLedger,
  StudentListItem,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { summaryToListItem } from '../../students/utils/mapToListItem';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'Statements'>;

export const StatementsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { palette, spacing, typography, radius } = useTheme();
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);

  const yearsQuery = useAcademicYearsSettings({ enabled: canView });
  const [yearId, setYearId] = useState<number | null>(null);
  const [termId, setTermId] = useState<number | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  useEffect(() => {
    const years = yearsQuery.data ?? [];
    if (!yearId && years.length) {
      setYearId((years.find((y) => y.is_active) ?? years[0]).id);
    }
  }, [yearsQuery.data, yearId]);

  const termsQuery = useTermsSettings(yearId ?? undefined, { enabled: canView && yearId != null });

  useEffect(() => {
    const terms = termsQuery.data ?? [];
    if (termId && !terms.some((t) => t.id === termId)) setTermId(null);
  }, [termsQuery.data, termId]);

  const statementFilters = useMemo(() => {
    if (termId) return { term_id: termId, detailed: true as const };
    if (yearId) return { academic_year_id: yearId, detailed: true as const };
    return { year: new Date().getFullYear(), detailed: true as const };
  }, [termId, yearId]);

  const listQuery = useInfiniteStudentList(
    { search: debouncedSearch || undefined, perPage: 15 },
    { enabled: canView && !selectedStudentId && debouncedSearch.length > 0 },
  );

  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const statementQuery = useStudentStatement(selectedStudentId ?? 0, statementFilters, {
    enabled: canView && selectedStudentId != null,
  });

  const filterTerms = statementQuery.data?.filters?.available_terms ?? termsQuery.data ?? [];
  const filterYears = statementQuery.data?.filters?.available_years ?? yearsQuery.data ?? [];
  const periodLabel = useMemo(() => {
    if (termId) {
      const t = filterTerms.find((x) => x.id === termId);
      const y = filterYears.find((x) => x.id === (t?.academic_year_id ?? yearId));
      return t ? `${t.name}${y ? ` ${y.year}` : ''}` : 'Selected term';
    }
    if (yearId) {
      const y = filterYears.find((x) => x.id === yearId);
      return y ? `Year ${y.year}` : 'Selected year';
    }
    return String(new Date().getFullYear());
  }, [termId, yearId, filterTerms, filterYears]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view statements."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <FinanceScreenHeader title="Statements" subtitle="Student fee statements" onBack={() => navigation.goBack()} />

        {selectedStudentId ? (
          <>
            <Pressable onPress={() => setSelectedStudentId(null)} style={{ marginBottom: spacing.sm }}>
              <Text style={{ color: palette.primary, fontWeight: '600' }}>Search another student</Text>
            </Pressable>

            <FilterChipRow label="Academic year">
              {filterYears.map((y) => (
                <FilterChip
                  key={y.id}
                  label={String(y.year)}
                  active={yearId === y.id && !termId}
                  onPress={() => {
                    setYearId(y.id);
                    setTermId(null);
                  }}
                />
              ))}
            </FilterChipRow>

            <FilterChipRow label="Term">
              {filterTerms.map((t) => (
                <FilterChip
                  key={t.id}
                  label={t.name}
                  active={termId === t.id}
                  onPress={() => {
                    setTermId(t.id);
                    setYearId(t.academic_year_id);
                  }}
                />
              ))}
            </FilterChipRow>
          </>
        ) : (
          <>
            <FinanceSearchBar
              value={searchInput}
              onChangeText={setSearchInput}
              placeholder="Search student by name or admission #…"
            />
            {debouncedSearch.length === 0 ? (
              <EmptyState
                title="Find a student"
                message="Type to search for a student and open their statement."
                icon="search-outline"
              />
            ) : listQuery.isLoading ? (
              <SkeletonListRows variant="avatar" count={5} />
            ) : listQuery.isError ? (
              <ListEmptyState
                title="Could not search students"
                message={(listQuery.error as Error).message}
                icon="alert-circle-outline"
                actionLabel="Retry"
                onAction={() => void listQuery.refetch()}
              />
            ) : students.length === 0 ? (
              <ListEmptyState
                title="No students found"
                message="No students match your search."
                icon="people-outline"
              />
            ) : (
              students.map((s) => (
                <StudentListItem
                  key={s.id}
                  student={summaryToListItem(s, () => setSelectedStudentId(s.id))}
                />
              ))
            )}
          </>
        )}

        {selectedStudentId ? (
          <View style={{ marginTop: spacing.md }}>
            {statementQuery.isLoading ? (
              <SkeletonListRows variant="compact" count={4} />
            ) : statementQuery.isError ? (
              <ListEmptyState
                title="Could not load statement"
                message={(statementQuery.error as Error).message}
                icon="alert-circle-outline"
                actionLabel="Retry"
                onAction={() => void statementQuery.refetch()}
              />
            ) : statementQuery.data ? (
              <>
                <Text
                  style={{
                    color: palette.textMain,
                    fontSize: typography.titleSmall.fontSize,
                    fontWeight: '700',
                    marginBottom: spacing.sm,
                  }}
                >
                  {statementQuery.data.student.full_name}
                </Text>
                <Text
                  style={{
                    color: palette.textSub,
                    fontSize: typography.body.fontSize,
                    marginBottom: spacing.md,
                  }}
                >
                  {statementQuery.data.student.admission_number} · {statementQuery.data.student.class_name} · {periodLabel}
                </Text>

                <View style={[styles.summaryRow, { marginBottom: spacing.md, gap: spacing.sm }]}>
                  <SummaryChip
                    label="Invoiced"
                    value={formatKes(statementQuery.data.total_invoiced)}
                    palette={palette}
                    typography={typography}
                    spacing={spacing}
                    radius={radius}
                  />
                  <SummaryChip
                    label="Paid"
                    value={formatKes(statementQuery.data.total_paid)}
                    palette={palette}
                    typography={typography}
                    spacing={spacing}
                    radius={radius}
                  />
                  <SummaryChip
                    label="Balance"
                    value={formatKes(statementQuery.data.closing_balance)}
                    palette={palette}
                    typography={typography}
                    spacing={spacing}
                    radius={radius}
                  />
                </View>

                <StatementLedger
                  rows={statementQuery.data.transactions.map((t) => ({
                    id: t.id,
                    date: t.date,
                    type: t.type,
                    reference: t.reference,
                    description: t.description,
                    votehead: t.votehead,
                    debit: t.debit,
                    credit: t.credit,
                    balance: t.balance,
                  }))}
                  formatAmount={formatKes}
                />
              </>
            ) : null}
          </View>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

function SummaryChip({
  label,
  value,
  palette,
  typography,
  spacing,
  radius,
}: {
  label: string;
  value: string;
  palette: { surface: string; border: string; textSub: string; textMain: string };
  typography: {
    overline: { fontSize: number };
    caption: { fontSize: number };
  };
  spacing: { sm: number };
  radius: { control: number };
}) {
  return (
    <View
      style={[
        styles.chip,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.control,
          padding: spacing.sm,
        },
      ]}
    >
      <Text style={{ color: palette.textSub, fontSize: typography.overline.fontSize, fontWeight: '700' }}>
        {label}
      </Text>
      <Text
        style={{
          color: palette.textMain,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
          marginTop: 2,
        }}
      >
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  summaryRow: { flexDirection: 'row', flexWrap: 'wrap' },
  chip: {
    flex: 1,
    minWidth: 100,
    borderWidth: StyleSheet.hairlineWidth,
  },
});
