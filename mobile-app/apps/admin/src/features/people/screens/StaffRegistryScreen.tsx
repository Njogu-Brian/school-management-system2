import {
  useCan,
  useInfiniteStaffList,
  useStaffFilterOptions,
  type StaffSummary,
} from '@erp/core';
import {
  ScreenContainer,
  StaffFilters,
  StaffListItem,
  StaffSearchBar,
  useTheme,
} from '@erp/ui';
import type { StaffEmploymentStatusFilterUi, StaffGenderFilterUi } from '@erp/ui';
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
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { useStaffRegistryState } from '../hooks/useStaffRegistryState';
import { summaryToListItem } from '../utils/mapToListItem';

export const StaffRegistryScreen: React.FC = () => {
  const canView = useCan(['people.view', 'staff.view']);
  const navigation = useNavigation<StackNavigationProp<PeopleStackParamList>>();
  const { palette, spacing, fontSizes } = useTheme();

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

  const openDetail = useCallback(
    (summary: StaffSummary) => {
      navigation.navigate('StaffDetail', { staffId: summary.id, summary });
    },
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.md, textAlign: 'center' }}>
          You need people.view permission to open the staff directory.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer style={{ flex: 1 }}>
      <FlatList
        data={staff}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <StaffSearchBar value={searchInput} onChangeText={setSearchInput} />
            {filterQuery.isLoading ? (
              <ActivityIndicator style={{ marginVertical: spacing.sm }} />
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
                onEmploymentStatusChange={(v) =>
                  setEmploymentStatus(v as typeof employmentStatus)
                }
                onGenderChange={(v) => setGender(v as typeof gender)}
              />
            )}
            {listQuery.data?.pages[0] != null ? (
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: fontSizes.sm,
                  marginBottom: spacing.sm,
                }}
              >
                {listQuery.data.pages[0].total} staff
              </Text>
            ) : null}
          </View>
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
            listQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.3}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? (
            <ActivityIndicator style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <ActivityIndicator style={{ marginTop: spacing.lg }} />
          ) : listQuery.isError ? (
            <View style={styles.empty}>
              <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>
                {(listQuery.error as Error)?.message ?? 'Failed to load staff.'}
              </Text>
              <Pressable onPress={() => listQuery.refetch()} style={{ marginTop: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>Retry</Text>
              </Pressable>
            </View>
          ) : (
            <Text
              style={{
                color: palette.textSecondary,
                textAlign: 'center',
                marginTop: spacing.lg,
              }}
            >
              No staff match your filters.
            </Text>
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', paddingHorizontal: 24 },
  empty: { alignItems: 'center', paddingTop: 24 },
});
