import { usePayrollRecordsList } from '@erp/core';
import {
  AcademicScreenHeader,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'PayrollRecords'>;

function buildMonthOptions(count = 12): Array<{ key: string | null; label: string }> {
  const options: Array<{ key: string | null; label: string }> = [{ key: null, label: 'All months' }];
  const now = new Date();
  for (let i = 0; i < count; i += 1) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const label = d.toLocaleString(undefined, { month: 'short', year: 'numeric' });
    options.push({ key, label });
  }
  return options;
}

export const PayrollRecordsScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, colors } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
  const [month, setMonth] = useState<string | null>(null);
  const monthOptions = useMemo(() => buildMonthOptions(12), []);
  const listQuery = usePayrollRecordsList({ month });
  const items = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Payroll records"
          subtitle="Filter by month · tap a row for full payslip"
          onBack={() => navigation.goBack()}
        />

        <FilterChipRow>
          {monthOptions.map((opt) => (
            <FilterChip
              key={opt.key ?? 'all'}
              label={opt.label}
              active={month === opt.key}
              onPress={() => setMonth(opt.key)}
            />
          ))}
        </FilterChipRow>

        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ paddingBottom: tabClearance, paddingTop: spacing.sm }}
          refreshControl={
            <RefreshControl
              refreshing={listQuery.isFetching && !listQuery.isLoading}
              onRefresh={() => void listQuery.refetch()}
              colors={[colors.primary]}
              tintColor={colors.primary}
            />
          }
          onEndReached={() => {
            if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
              void listQuery.fetchNextPage();
            }
          }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('PayrollDetail', { recordId: item.id })}
              style={({ pressed }) => [
                styles.row,
                {
                  opacity: pressed ? 0.92 : 1,
                  borderColor: palette.borderSubtle,
                  paddingVertical: spacing.md,
                  backgroundColor: palette.surfaceRaised,
                  borderRadius: 12,
                  paddingHorizontal: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <Text
                style={{
                  color: palette.textPrimary,
                  fontWeight: '600',
                  fontSize: typography.body.fontSize,
                }}
              >
                {item.staff_name ?? `Staff #${item.staff_id}`}
              </Text>
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                  marginTop: 2,
                }}
              >
                {item.period_name ?? item.month ?? '—'} · {item.status}
              </Text>
              <Text
                style={{
                  color: palette.primary,
                  fontWeight: '700',
                  fontSize: typography.titleSmall.fontSize,
                  marginTop: spacing.xs,
                }}
              >
                KES {Number(item.net_salary ?? 0).toLocaleString()}
              </Text>
            </Pressable>
          )}
          ListEmptyComponent={
            listQuery.isLoading ? (
              <SkeletonListRows variant="compact" count={6} />
            ) : listQuery.isError ? (
              <ListEmptyState
                title="Could not load payroll"
                message={(listQuery.error as Error)?.message ?? 'Failed to load payroll records.'}
                icon="alert-circle-outline"
                actionLabel="Retry"
                onAction={() => void listQuery.refetch()}
              />
            ) : (
              <ListEmptyState entityName="payroll records" icon="wallet-outline" />
            )
          }
        />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: {
    borderWidth: StyleSheet.hairlineWidth,
  },
});
