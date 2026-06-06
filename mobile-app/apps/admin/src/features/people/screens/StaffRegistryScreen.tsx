import {
  useCan,
  useInfiniteStaffList,
  useStaffFilterOptions,
  type StaffSummary,
} from '@erp/core';
import {
  countActiveFilters,
  DashboardHero,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SkeletonListRows,
  StaffFilters,
  StaffListItem,
  StaffSearchBar,
  useTheme,
} from '@erp/ui';
import type { StaffEmploymentStatusFilterUi, StaffGenderFilterUi } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
  Text,
} from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { useStaffRegistryState } from '../hooks/useStaffRegistryState';
import { summaryToListItem } from '../utils/mapToListItem';

export const StaffRegistryScreen: React.FC = () => {
  const canView = useCan(['people.view', 'staff.view']);
  const navigation = useNavigation<StackNavigationProp<PeopleStackParamList>>();
  const { colors, palette, typography } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);

  const {
    searchInput,
    setSearchInput,
    departmentId,
    setDepartmentId,
    staffCategoryId,
    setStaffCategoryId,
    role,
    setRole,
    employmentStatus,
    setEmploymentStatus,
    gender,
    setGender,
    filters,
  } = useStaffRegistryState();

  const filterQuery = useStaffFilterOptions({ enabled: canView });
  const listQuery = useInfiniteStaffList(filters, { enabled: canView });

  const filterOpts = filterQuery.data;

  const departmentOptions = useMemo(
    () => (filterOpts?.departments ?? []).map((d) => ({ value: d.id, label: d.name })),
    [filterOpts],
  );
  const categoryOptions = useMemo(
    () => (filterOpts?.categories ?? []).map((c) => ({ value: c.id, label: c.name })),
    [filterOpts],
  );
  const roleOptions = useMemo(
    () => (filterOpts?.roles ?? []).map((r) => ({ value: r, label: r })),
    [filterOpts],
  );
  const employmentStatusOptions = useMemo(
    () => [
      { value: 'all' as StaffEmploymentStatusFilterUi, label: 'All' },
      ...(filterOpts?.employmentStatuses ?? []).map((o) => ({
        value: o.value as StaffEmploymentStatusFilterUi,
        label: o.label,
      })),
    ],
    [filterOpts],
  );
  const genderOptions = useMemo(
    () => [
      { value: 'all' as StaffGenderFilterUi, label: 'All' },
      ...(filterOpts?.genders ?? []).map((o) => ({
        value: o.value as StaffGenderFilterUi,
        label: o.label,
      })),
    ],
    [filterOpts],
  );

  const staff = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const totalStaff = listQuery.data?.pages[0]?.total;

  const activeFilterCount = countActiveFilters([
    departmentId,
    staffCategoryId,
    role,
    employmentStatus,
    gender,
  ]);

  const openDetail = useCallback(
    (summary: StaffSummary) => {
      navigation.navigate('StaffDetail', { staffId: summary.id, summary });
    },
    [navigation],
  );

  const clearFilters = useCallback(() => {
    setSearchInput('');
    setDepartmentId(null);
    setStaffCategoryId(null);
    setRole(null);
    setEmploymentStatus('all');
    setGender('all');
    setFiltersOpen(false);
  }, [setSearchInput, setDepartmentId, setStaffCategoryId, setRole, setEmploymentStatus, setGender]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize, textAlign: 'center' }}>
          You need people.view permission to open the staff directory.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={staff}
        keyExtractor={(item) => String(item.id)}
        hero={
          <DashboardHero
            variant="people"
            title="People Directory"
            subtitle="Staff registry & employment records"
            meta={totalStaff != null ? `${totalStaff} staff members` : undefined}
          />
        }
        searchBar={<StaffSearchBar value={searchInput} onChangeText={setSearchInput} />}
        activeFilterCount={activeFilterCount}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={clearFilters}
        filterContent={
          filterQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <StaffFilters
              departmentId={departmentId}
              staffCategoryId={staffCategoryId}
              role={role}
              employmentStatus={employmentStatus as StaffEmploymentStatusFilterUi}
              gender={gender as StaffGenderFilterUi}
              departmentOptions={departmentOptions}
              categoryOptions={categoryOptions}
              roleOptions={roleOptions}
              employmentStatusOptions={employmentStatusOptions}
              genderOptions={genderOptions}
              onDepartmentChange={setDepartmentId}
              onCategoryChange={setStaffCategoryId}
              onRoleChange={setRole}
              onEmploymentStatusChange={(v) => setEmploymentStatus(v as typeof employmentStatus)}
              onGenderChange={(v) => setGender(v as typeof gender)}
            />
          )
        }
        renderItem={({ item }) => (
          <StaffListItem staff={summaryToListItem(item, () => openDetail(item))} />
        )}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => listQuery.refetch()}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
            void listQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.3}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? (
            <ActivityIndicator style={{ marginVertical: 16 }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="avatar" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load staff"
              message={(listQuery.error as Error)?.message ?? 'Failed to load staff.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => listQuery.refetch()}
            />
          ) : (
            <ListEmptyState entityName="staff" icon="people-outline" onClearFilters={clearFilters} />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', paddingHorizontal: 24 },
});
