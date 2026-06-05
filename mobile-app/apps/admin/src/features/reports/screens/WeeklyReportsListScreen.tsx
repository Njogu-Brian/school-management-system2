import { useCan, useWeeklyReports } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { capitalizeStatus, formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'WeeklyReportsList'>;

export const WeeklyReportsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useWeeklyReports({ enabled: canView });

  const items = query.data?.items ?? [];

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
        keyExtractor={(item) => `${item.type}-${item.id}`}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader title="Weekly reports" onBack={() => navigation.goBack()} />
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('WeeklyReportDetail', { type: item.type, reportId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ fontWeight: '600', color: palette.textPrimary }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
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
          />
        }
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : query.isError ? (
            <Pressable onPress={() => void query.refetch()}>
              <Text style={{ color: colors.error, textAlign: 'center' }}>Retry</Text>
            </Pressable>
          ) : (
            <EmptyState title="No weekly reports" message="No reports have been submitted yet." icon="calendar-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
