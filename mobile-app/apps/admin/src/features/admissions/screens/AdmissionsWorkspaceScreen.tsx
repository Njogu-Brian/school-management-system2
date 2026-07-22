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
  FilterChip,
  FilterChipRow,
  countActiveFilters,
  DashboardHero,
  EmptyState,
  KpiCard,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SkeletonListRows,
  SkeletonWidgetGrid,
  WidgetGrid,
  WidgetShell,
  applicationStatusLabel,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AdmissionsStackParamList } from '../../../navigation/admissionsStackTypes';
import { AdmissionsFunnelChart } from '../components/AdmissionsFunnelChart';
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
  const { colors, palette, spacing, typography } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);

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

  const stats = statsQuery.data;
  const totalApplications =
    (stats?.pending ?? 0) +
    (stats?.under_review ?? 0) +
    (stats?.waitlisted ?? 0) +
    (stats?.enrolled ?? 0) +
    (stats?.rejected ?? 0);

  const activeFilterCount = countActiveFilters([status]);

  const clearFilters = useCallback(() => {
    setSearchInput('');
    setStatus('all');
    setFiltersOpen(false);
  }, [setSearchInput, setStatus]);

  const hero = useMemo(
    () => (
      <View>
        <DashboardHero
          variant="admissions"
          title="Admissions Dashboard"
          subtitle="Applications & enrollment pipeline"
          meta={totalApplications > 0 ? `${totalApplications} total applications` : undefined}
        />

        {statsQuery.isLoading ? (
          <SkeletonWidgetGrid count={4} />
        ) : (
          <WidgetGrid>
            {KPI_CONFIG.map((kpi) => {
              const value = stats?.[kpi.field];
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
        )}

        {stats ? (
          <View style={{ marginVertical: spacing.md }}>
            <AdmissionsFunnelChart
              pending={stats.pending ?? 0}
              underReview={stats.under_review ?? 0}
              waitlisted={stats.waitlisted ?? 0}
              enrolled={stats.enrolled ?? 0}
              rejected={stats.rejected ?? 0}
            />
          </View>
        ) : null}

        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.title.fontSize,
            fontWeight: typography.title.fontWeight,
            marginTop: spacing.sm,
            marginBottom: spacing.xs,
          }}
        >
          Applications
          {status !== 'all' ? ` · ${applicationStatusLabel(status)}` : ''}
        </Text>
        <FilterChipRow>
          {(
            [
              'all',
              'pending',
              'under_review',
              'waitlisted',
              'enrolled',
              'rejected',
            ] as const
          ).map((option) => (
            <FilterChip
              key={option}
              label={option === 'all' ? 'All' : applicationStatusLabel(option)}
              active={status === option}
              onPress={() => setStatus(option)}
            />
          ))}
        </FilterChipRow>
      </View>
    ),
    [palette.textPrimary, spacing, statsQuery, stats, totalApplications, setStatus, status, typography],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need admissions.view permission to open the admissions workspace."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={styles.flex}>
      <RegistryListLayout
        data={applications}
        keyExtractor={(item) => String(item.id)}
        hero={hero}
        searchBar={<ApplicationSearchBar value={searchInput} onChangeText={setSearchInput} />}
        activeFilterCount={activeFilterCount}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={clearFilters}
        filterContent={<ApplicationFilters status={status} onStatusChange={setStatus} />}
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.sm }}>
            <ApplicationListItem application={summaryToListItem(item)} onPress={() => openDetail(item)} />
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
            <ActivityIndicator color={colors.primary} style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="avatar" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load applications"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              entityName="applications"
              icon="document-text-outline"
              onClearFilters={clearFilters}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
