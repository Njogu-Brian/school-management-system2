import {
  useAdmissionsStats,
  useCan,
  useInfiniteApplicationList,
  type ApplicationSummary,
} from '@erp/core';
import {
  ApplicationFilters,
  ApplicationListItem,
  ApplicationSearchBar,
  KpiCard,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AdmissionsStackParamList } from '../../../navigation/admissionsStackTypes';
import { useApplicationRegistryState } from '../hooks/useApplicationRegistryState';
import { summaryToListItem } from '../utils/mapToListItem';

const KPI_CONFIG = [
  { status: 'pending' as const, label: 'Pending', icon: 'time-outline' as const, field: 'pending' as const },
  { status: 'under_review' as const, label: 'Under Review', icon: 'eye-outline' as const, field: 'under_review' as const },
  { status: 'waitlisted' as const, label: 'Waitlisted', icon: 'list-outline' as const, field: 'waitlisted' as const },
  { status: 'enrolled' as const, label: 'Enrolled', icon: 'school-outline' as const, field: 'enrolled' as const },
  { status: 'rejected' as const, label: 'Rejected', icon: 'close-circle-outline' as const, field: 'rejected' as const },
];

export const AdmissionsWorkspaceScreen: React.FC = () => {
  const canView = useCan('admissions.view');
  const navigation = useNavigation<StackNavigationProp<AdmissionsStackParamList>>();
  const { colors, palette, spacing, fontSizes } = useTheme();

  const { searchInput, setSearchInput, status, setStatus, filters } = useApplicationRegistryState();
  const statsQuery = useAdmissionsStats({ enabled: canView });
  const listQuery = useInfiniteApplicationList(filters, { enabled: canView });

  const applications = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const openDetail = useCallback(
    (summary: ApplicationSummary) => {
      navigation.navigate('ApplicationDetail', { applicationId: summary.id, summary });
    },
    [navigation],
  );

  const listHeader = useMemo(
    () => (
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.sm }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Admissions Dashboard
        </Text>
        <WidgetGrid>
          {KPI_CONFIG.map((kpi) => {
            const value = statsQuery.data?.[kpi.field];
            const state = statsQuery.isLoading
              ? 'loading'
              : statsQuery.isError
                ? 'error'
                : value == null
                  ? 'empty'
                  : 'success';
            return (
              <WidgetShell
                key={kpi.field}
                state={state}
                title={kpi.label}
                onRetry={() => void statsQuery.refetch()}
              >
                <KpiCard
                  label={kpi.label}
                  value={String(value ?? 0)}
                  icon={kpi.icon}
                  onPress={() => setStatus(kpi.status)}
                />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        <Text
          style={{
            color: palette.textPrimary,
            fontSize: fontSizes.md,
            fontWeight: '700',
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          }}
        >
          Applications
        </Text>
        <ApplicationSearchBar value={searchInput} onChangeText={setSearchInput} />
        <ApplicationFilters status={status} onStatusChange={setStatus} />
      </View>
    ),
    [
      spacing,
      palette,
      fontSizes,
      statsQuery,
      searchInput,
      status,
      setSearchInput,
      setStatus,
    ],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.md, textAlign: 'center' }}>
          You need admissions.view permission to open the admissions workspace.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={applications}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={listHeader}
        renderItem={({ item }) => (
          <View style={{ paddingHorizontal: spacing.md, marginBottom: spacing.sm }}>
            <ApplicationListItem
              application={summaryToListItem(item)}
              onPress={() => openDetail(item)}
            />
          </View>
        )}
        refreshControl={
          <RefreshControl
            refreshing={
              (listQuery.isRefetching && !listQuery.isFetchingNextPage) || statsQuery.isRefetching
            }
            onRefresh={() => {
              void listQuery.refetch();
              void statsQuery.refetch();
            }}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
            void listQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} style={{ marginVertical: 16 }} />
          ) : null
        }
        ListEmptyComponent={
          !listQuery.isLoading && !listQuery.isError ? (
            <Text
              style={{
                color: palette.textSecondary,
                textAlign: 'center',
                marginTop: spacing.lg,
                fontSize: fontSizes.sm,
                paddingHorizontal: spacing.md,
              }}
            >
              No applications match your filters.
            </Text>
          ) : null
        }
        contentContainerStyle={{ paddingBottom: spacing.xl }}
      />

      {listQuery.isError ? (
        <View style={{ padding: spacing.md }}>
          <Text style={{ color: colors.error, textAlign: 'center' }}>
            {(listQuery.error as Error).message}
          </Text>
          <Pressable onPress={() => void listQuery.refetch()} style={{ marginTop: spacing.sm, alignSelf: 'center' }}>
            <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
          </Pressable>
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
