import { useCan, useInfiniteAssets } from '@erp/core';
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
  TextInput,
  View,
} from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { capitalizeStatus } from '../../shared/utils/formatters';

type Props = StackScreenProps<OperationsStackParamList, 'AssetsList'>;

const STATUS_FILTERS = ['all', 'active', 'assigned', 'maintenance', 'retired'] as const;

export const AssetsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<(typeof STATUS_FILTERS)[number]>('all');

  const listQuery = useInfiniteAssets({
    enabled: canView,
    search: search.trim() || undefined,
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
            <AcademicScreenHeader title="Fixed assets" onBack={() => navigation.goBack()} />
            <TextInput
              value={search}
              onChangeText={setSearch}
              placeholder="Search assets"
              placeholderTextColor={palette.textSecondary}
              style={[styles.search, { borderColor: palette.border, color: palette.textPrimary }]}
            />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: spacing.sm }}>
              {STATUS_FILTERS.map((s) => (
                <Pressable
                  key={s}
                  onPress={() => setStatus(s)}
                  style={[styles.chip, { borderColor: palette.border }, status === s && { borderColor: colors.primary }]}
                >
                  <Text style={{ fontSize: fontSizes.xs }}>{capitalizeStatus(s)}</Text>
                </Pressable>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('AssetDetail', { assetId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ fontWeight: '600', color: palette.textPrimary }}>{item.name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
              {[item.asset_tag, item.category, capitalizeStatus(item.status)].filter(Boolean).join(' · ')}
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
            <EmptyState title="No assets" message="No fixed assets match your filters." icon="hardware-chip-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  search: { borderWidth: 1, borderRadius: 8, padding: 12, marginBottom: 12 },
  chip: { borderWidth: 1, borderRadius: 16, paddingHorizontal: 10, paddingVertical: 6 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
