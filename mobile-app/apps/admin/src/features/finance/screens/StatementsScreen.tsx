import { useCan, useInfiniteStudentList, useStudentStatement } from '@erp/core';
import {
  FinanceScreenHeader,
  FinanceSearchBar,
  ScreenContainer,
  StatementLedger,
  StudentListItem,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
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
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const statementYear = new Date().getFullYear();

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  const listQuery = useInfiniteStudentList(
    { search: debouncedSearch || undefined, perPage: 15 },
    { enabled: canView && !selectedStudentId && debouncedSearch.length > 0 },
  );

  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const statementQuery = useStudentStatement(selectedStudentId ?? 0, statementYear, {
    enabled: canView && selectedStudentId != null,
  });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <FinanceScreenHeader title="Statements" subtitle="Student fee statements" onBack={() => navigation.goBack()} />

        {selectedStudentId ? (
          <Pressable onPress={() => setSelectedStudentId(null)} style={{ marginBottom: spacing.sm }}>
            <Text style={{ color: colors.primary, fontWeight: '600' }}>← Search another student</Text>
          </Pressable>
        ) : (
          <>
            <FinanceSearchBar
              value={searchInput}
              onChangeText={setSearchInput}
              placeholder="Search student by name or admission #…"
            />
            {debouncedSearch.length === 0 ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
                Type to search for a student and open their statement.
              </Text>
            ) : listQuery.isLoading ? (
              <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.lg }} />
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
              <ActivityIndicator color={colors.primary} />
            ) : statementQuery.isError ? (
              <View>
                <Text style={{ color: colors.error }}>{(statementQuery.error as Error).message}</Text>
                <Pressable onPress={() => void statementQuery.refetch()}>
                  <Text style={{ color: colors.primary, marginTop: spacing.sm, fontWeight: '600' }}>Retry</Text>
                </Pressable>
              </View>
            ) : statementQuery.data ? (
              <>
                <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700', marginBottom: spacing.sm }}>
                  {statementQuery.data.student.full_name}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginBottom: spacing.md }}>
                  {statementQuery.data.student.admission_number} · {statementQuery.data.student.class_name} · {statementYear}
                </Text>

                <View style={[styles.summaryRow, { marginBottom: spacing.md, gap: spacing.sm }]}>
                  <SummaryChip label="Invoiced" value={formatKes(statementQuery.data.total_invoiced)} palette={palette} />
                  <SummaryChip label="Paid" value={formatKes(statementQuery.data.total_paid)} palette={palette} />
                  <SummaryChip label="Balance" value={formatKes(statementQuery.data.closing_balance)} palette={palette} />
                </View>

                <StatementLedger
                  rows={statementQuery.data.transactions.map((t) => ({
                    id: t.id,
                    date: t.date,
                    type: t.type,
                    reference: t.reference,
                    description: t.description,
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
}: {
  label: string;
  value: string;
  palette: { surface: string; border: string; textSecondary: string; textPrimary: string };
}) {
  return (
    <View style={[styles.chip, { backgroundColor: palette.surface, borderColor: palette.border }]}>
      <Text style={{ color: palette.textSecondary, fontSize: 10, fontWeight: '700' }}>{label}</Text>
      <Text style={{ color: palette.textPrimary, fontSize: 12, fontWeight: '700', marginTop: 2 }}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  summaryRow: { flexDirection: 'row', flexWrap: 'wrap' },
  chip: {
    flex: 1,
    minWidth: 100,
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 8,
    padding: 8,
  },
});
