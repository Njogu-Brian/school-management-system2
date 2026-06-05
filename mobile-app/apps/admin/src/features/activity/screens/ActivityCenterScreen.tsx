import { useInfiniteAuditTrail } from '@erp/core';
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
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<DashboardStackParamList, 'ActivityCenter'>;

const MODULE_FILTERS = ['all', 'Finance', 'Admissions', 'Students', 'HR', 'Visitors', 'Communication', 'Approvals', 'Security'];

export const ActivityCenterScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [search, setSearch] = useState('');
  const [moduleFilter, setModuleFilter] = useState('all');

  const auditQuery = useInfiniteAuditTrail({
    search: search.trim() || undefined,
    module: moduleFilter === 'all' ? undefined : moduleFilter,
  });

  const rows = useMemo(
    () => auditQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [auditQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={rows}
        keyExtractor={(item) => item.id}
        contentContainerStyle={{ padding: spacing.md }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="Audit trail" onBack={() => navigation.goBack()} />
            <TextInput
              value={search}
              onChangeText={setSearch}
              placeholder="Search actions"
              placeholderTextColor={palette.textSecondary}
              style={[styles.input, { borderColor: palette.border, color: palette.textPrimary }]}
            />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: spacing.sm }}>
              {MODULE_FILTERS.map((m) => (
                <Pressable
                  key={m}
                  onPress={() => setModuleFilter(m)}
                  style={[styles.chip, moduleFilter === m && { borderColor: colors.primary }]}
                >
                  <Text style={{ fontSize: fontSizes.xs }}>{m === 'all' ? 'All' : m}</Text>
                </Pressable>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('AuditDetail', { auditId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ fontWeight: '600', color: palette.textPrimary }}>{item.action}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }} numberOfLines={2}>
              {item.target}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
              {[item.user, item.module, formatDateTimeLabel(item.timestamp)].filter(Boolean).join(' · ')}
            </Text>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={auditQuery.isRefetching && !auditQuery.isFetchingNextPage}
            onRefresh={() => void auditQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (auditQuery.hasNextPage && !auditQuery.isFetchingNextPage) {
            void auditQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={auditQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
        ListEmptyComponent={
          auditQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <EmptyState title="No activity" message="Audit events will appear here." icon="time-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  input: { borderWidth: 1, borderRadius: 8, padding: 10, marginBottom: 8 },
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 14, paddingHorizontal: 8, paddingVertical: 4 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
