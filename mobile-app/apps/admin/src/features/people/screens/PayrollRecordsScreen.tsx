import { usePayrollRecordsList } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'PayrollRecords'>;

export const PayrollRecordsScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, colors } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
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
          subtitle="School-wide payslips and net pay"
          onBack={() => navigation.goBack()}
        />
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ paddingBottom: tabClearance }}
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
            <View
              style={[
                styles.row,
                {
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
                {item.period_name ?? item.month ?? '—'}
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
  row: {
    borderWidth: StyleSheet.hairlineWidth,
  },
});
