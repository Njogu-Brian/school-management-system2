import { usePayrollRecordsList } from '@erp/core';
import { AcademicScreenHeader, ListEmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'PayrollRecords'>;

export const PayrollRecordsScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const listQuery = usePayrollRecordsList();
  const items = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader title="Payroll records" subtitle="School-wide payroll" onBack={() => navigation.goBack()} />
        {listQuery.isLoading ? <ActivityIndicator color={colors.primary} /> : null}
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          onEndReached={() => {
            if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) void listQuery.fetchNextPage();
          }}
          renderItem={({ item }) => (
            <View style={[styles.row, { borderColor: palette.border }]}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                {item.staff_name ?? `Staff #${item.staff_id}`}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
                {item.period_name ?? item.month ?? '—'} · KES {item.net_salary ?? 0}
              </Text>
            </View>
          )}
          ListEmptyComponent={
            !listQuery.isLoading ? <ListEmptyState entityName="payroll records" icon="wallet-outline" /> : null
          }
        />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderBottomWidth: StyleSheet.hairlineWidth, paddingVertical: 12 },
});
