import { useCan, useWeeklyReports } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, RefreshControl, StyleSheet, Text } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { capitalizeStatus, formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'WeeklyReportsList'>;

export const WeeklyReportsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const [search, setSearch] = useState('');
  const query = useWeeklyReports({ enabled: canView });

  const items = useMemo(() => {
    const all = query.data?.items ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return all;
    return all.filter(
      (r) =>
        r.title.toLowerCase().includes(q) ||
        r.type.toLowerCase().includes(q) ||
        (r.subtitle ?? '').toLowerCase().includes(q),
    );
  }, [query.data, search]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need reports.view permission to view weekly reports."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={items}
        keyExtractor={(item) => `${item.type}-${item.id}`}
        showFilterTrigger={false}
        hero={
          <AcademicScreenHeader
            title="Weekly reports"
            subtitle="Staff, class & facility submissions"
            onBack={() => navigation.goBack()}
          />
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search reports…" />}
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('WeeklyReportDetail', { type: item.type, reportId: item.id })}
            style={({ pressed }) => [
              elevation[1],
              {
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: palette.borderSubtle,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <Text
              style={{
                fontWeight: typography.titleSmall.fontWeight,
                color: palette.textPrimary,
                fontSize: typography.titleSmall.fontSize,
                lineHeight: typography.titleSmall.lineHeight,
              }}
            >
              {item.title}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                fontWeight: typography.caption.fontWeight,
                lineHeight: typography.caption.lineHeight,
                marginTop: spacing.xs,
              }}
            >
              {[capitalizeStatus(item.type.replace(/_/g, ' ')), formatDateLabel(item.week_ending), item.subtitle]
                .filter(Boolean)
                .join(' · ')}
            </Text>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListEmptyComponent={
          query.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : query.isError ? (
            <ListEmptyState
              title="Could not load reports"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No weekly reports"
              message={search ? 'No reports match your search.' : 'No reports have been submitted yet.'}
              icon="calendar-outline"
              onClearFilters={search ? () => setSearch('') : undefined}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
