import { useCan, useInfiniteVisitors } from '@erp/core';
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
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'VisitorsList'>;

type Filter = 'all' | 'on_site' | 'checked_out';

export const VisitorsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [filter, setFilter] = useState<Filter>('all');

  const listQuery = useInfiniteVisitors({
    enabled: canView,
    onSite: filter === 'on_site' ? true : filter === 'checked_out' ? false : undefined,
  });

  const items = useMemo(() => {
    const rows = listQuery.data?.pages.flatMap((p) => p.items) ?? [];
    if (filter === 'checked_out') return rows.filter((v) => !v.on_site);
    return rows;
  }, [listQuery.data, filter]);

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
          <View>
            <AcademicScreenHeader title="Visitors" onBack={() => navigation.goBack()} />
            <Pressable onPress={() => navigation.navigate('VisitorCheckIn')} style={{ marginBottom: spacing.sm }}>
              <Text style={{ color: colors.primary, fontWeight: '600' }}>+ Check in visitor</Text>
            </Pressable>
            <View style={{ flexDirection: 'row', gap: 8, marginBottom: spacing.sm }}>
              {(['all', 'on_site', 'checked_out'] as Filter[]).map((f) => (
                <Pressable
                  key={f}
                  onPress={() => setFilter(f)}
                  style={[styles.chip, filter === f && { borderColor: colors.primary }]}
                >
                  <Text style={{ fontSize: fontSizes.xs }}>{f.replace('_', ' ')}</Text>
                </Pressable>
              ))}
            </View>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('VisitorDetail', { visitorId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
              {item.visitor_name}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {[item.purpose, item.host_name, item.on_site ? 'On site' : 'Checked out'].filter(Boolean).join(' · ')}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {formatDateTimeLabel(item.checked_in_at)}
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
            <EmptyState title="No visitors" message="No visitor records match your filter." icon="person-outline" />
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
