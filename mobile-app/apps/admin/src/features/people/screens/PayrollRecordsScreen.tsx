import { usePayrollRecordsList } from '@erp/core';
import { AcademicScreenHeader, ListEmptyState, ScreenContainer, SkeletonListRows, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'PayrollRecords'>;

export const PayrollRecordsScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography } = useTheme();
  const listQuery = usePayrollRecordsList();
  const items = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Payroll records"
          subtitle="School-wide payroll"
          onBack={() => navigation.goBack()}
        />
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ paddingBottom: spacing.xl }}
          onEndReached={() => {
            if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) void listQuery.fetchNextPage();
          }}
          renderItem={({ item }) => (
            <View style={[styles.row, { borderColor: palette.borderSubtle, paddingVertical: spacing.md }]}>
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
                {item.period_name ?? item.month ?? '—'} · KES {item.net_salary ?? 0}
              </Text>
            </View>
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
  row: { borderBottomWidth: StyleSheet.hairlineWidth },
});
