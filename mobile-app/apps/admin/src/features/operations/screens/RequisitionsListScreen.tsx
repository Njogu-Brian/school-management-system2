import { useCan, useInfiniteRequisitions } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { capitalizeStatus } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'RequisitionsList'>;

const STATUS_FILTERS = ['all', 'pending', 'approved', 'rejected', 'fulfilled'] as const;

export const RequisitionsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [status, setStatus] = useState<(typeof STATUS_FILTERS)[number]>('pending');

  const listQuery = useInfiniteRequisitions({
    enabled: canView,
    status: status === 'all' ? undefined : status,
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
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="Requisitions" onBack={() => navigation.goBack()} />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: spacing.sm }}>
              {STATUS_FILTERS.map((s) => (
                <Pressable
                  key={s}
                  onPress={() => setStatus(s)}
                  style={[styles.chip, status === s && { borderColor: colors.primary }]}
                >
                  <Text style={{ fontSize: fontSizes.xs }}>{capitalizeStatus(s)}</Text>
                </Pressable>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('RequisitionDetail', { requisitionId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
              {item.requisition_number}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {[item.requested_by, capitalizeStatus(item.type), capitalizeStatus(item.status)].filter(Boolean).join(' · ')}
            </Text>
          </Pressable>
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
            <ActivityIndicator color={colors.primary} />
          ) : (
            <EmptyState title="No requisitions" message="No requisitions match your filter." icon="clipboard-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 16, paddingHorizontal: 10, paddingVertical: 6 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
