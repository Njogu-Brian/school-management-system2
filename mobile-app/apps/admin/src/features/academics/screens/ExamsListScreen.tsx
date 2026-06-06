import {
  useAcademicYearsSettings,
  useCan,
  useInfiniteExams,
  useTermsSettings,
  type ExamSummary,
} from '@erp/core';
import {
  AcademicScreenHeader,
  AcademicSearchBar,
  countActiveFilters,
  ExamFilters,
  ExamListItem,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { useExamsRegistryState } from '../hooks/useExamsRegistryState';

type Props = StackScreenProps<AcademicsStackParamList, 'ExamsList'>;

export const ExamsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing, fontSizes, radius } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);
  const {
    searchInput,
    setSearchInput,
    status,
    setStatus,
    termId,
    setTermId,
    academicYearId,
    setAcademicYearId,
    filters,
  } = useExamsRegistryState();

  const yearsQuery = useAcademicYearsSettings({ enabled: canView });
  const termsQuery = useTermsSettings(academicYearId ?? undefined, {
    enabled: canView && academicYearId != null,
  });
  const listQuery = useInfiniteExams(filters, { enabled: canView });

  const exams = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const activeFilterCount = countActiveFilters([status, academicYearId, termId]);

  const openDetail = useCallback(
    (summary: ExamSummary) => {
      navigation.navigate('ExamDetail', { examId: summary.id, summary });
    },
    [navigation],
  );

  const clearFilters = useCallback(() => {
    setSearchInput('');
    setStatus('all');
    setAcademicYearId(null);
    setTermId(null);
    setFiltersOpen(false);
  }, [setSearchInput, setStatus, setAcademicYearId, setTermId]);

  const filterContent = (
    <View>
      <ExamFilters status={status} onStatusChange={setStatus} />
      <Text style={[styles.sectionLabel, { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.sm }]}>
        Academic year
      </Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
        {(yearsQuery.data ?? []).map((y) => (
          <Pressable
            key={y.id}
            onPress={() => {
              setAcademicYearId(academicYearId === y.id ? null : y.id);
              setTermId(null);
            }}
            style={[
              styles.chip,
              {
                backgroundColor: academicYearId === y.id ? colors.primary : palette.surface,
                borderColor: academicYearId === y.id ? colors.primary : palette.border,
                borderRadius: radius.full,
                marginRight: spacing.xs,
              },
            ]}
          >
            <Text
              style={{
                color: academicYearId === y.id ? colors.white : palette.textSecondary,
                fontSize: fontSizes.xs,
                fontWeight: '700',
              }}
            >
              {y.label}
            </Text>
          </Pressable>
        ))}
      </ScrollView>
      {(termsQuery.data ?? []).length > 0 ? (
        <>
          <Text style={[styles.sectionLabel, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>Term</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {(termsQuery.data ?? []).map((t) => (
              <Pressable
                key={t.id}
                onPress={() => setTermId(termId === t.id ? null : t.id)}
                style={[
                  styles.chip,
                  {
                    backgroundColor: termId === t.id ? colors.primary : palette.surface,
                    borderColor: termId === t.id ? colors.primary : palette.border,
                    borderRadius: radius.full,
                    marginRight: spacing.xs,
                  },
                ]}
              >
                <Text
                  style={{
                    color: termId === t.id ? colors.white : palette.textSecondary,
                    fontSize: fontSizes.xs,
                    fontWeight: '700',
                  }}
                >
                  {t.name}
                </Text>
              </Pressable>
            ))}
          </ScrollView>
        </>
      ) : null}
    </View>
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={exams}
        keyExtractor={(item) => String(item.id)}
        hero={
          <AcademicScreenHeader title="Exams" subtitle="Read-only exam registry" onBack={() => navigation.goBack()} />
        }
        searchBar={
          <AcademicSearchBar value={searchInput} onChangeText={setSearchInput} placeholder="Search exams…" />
        }
        activeFilterCount={activeFilterCount}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={clearFilters}
        filterContent={filterContent}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        renderItem={({ item }) => (
          <ExamListItem
            exam={{
              id: item.id,
              name: item.name,
              status: item.status,
              examTypeName: item.examTypeName,
              classroomName: item.classroomName,
              subjectName: item.subjectName,
              startDate: item.startDate,
              onPress: () => openDetail(item),
            }}
          />
        )}
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="compact" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load exams"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState entityName="exams" icon="school-outline" onClearFilters={clearFilters} />
          )
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
            void listQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.3}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} /> : null
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderWidth: StyleSheet.hairlineWidth },
  sectionLabel: { fontWeight: '600', marginBottom: 6, textTransform: 'uppercase' },
});
