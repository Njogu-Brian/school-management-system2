import { useCan, useOperationsSummary } from '@erp/core';
import { KpiCard, QuickAction, ScreenContainer, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'OperationsDashboard'>;

const SECTIONS = [
  { route: 'TripsList' as const, label: 'Transport', icon: 'bus-outline' as const, subtitle: 'School trips & routes' },
];

export const OperationsDashboardScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const summaryQuery = useOperationsSummary({ enabled: canView });

  const openSection = useCallback(
    (route: (typeof SECTIONS)[number]['route']) => navigation.navigate(route),
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const s = summaryQuery.data;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={summaryQuery.isRefetching}
            onRefresh={() => void summaryQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Operations
        </Text>

        <WidgetGrid>
          {[
            { label: 'Active trips', value: String(s?.transport.active_trips ?? '—'), icon: 'bus-outline' as const },
            { label: 'Students on transport', value: String(s?.transport.students_assigned ?? '—'), icon: 'people-outline' as const },
            { label: 'Library books', value: String(s?.library.total_books ?? '—'), icon: 'library-outline' as const },
            { label: 'Books available', value: String(s?.library.available_books ?? '—'), icon: 'book-outline' as const },
          ].map((kpi) => {
            const state = summaryQuery.isLoading ? 'loading' : summaryQuery.isError ? 'error' : 'success';
            return (
              <WidgetShell key={kpi.label} state={state} title={kpi.label}>
                <KpiCard label={kpi.label} value={kpi.value} icon={kpi.icon} />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Workspaces
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {SECTIONS.map((section) => (
            <Pressable key={section.route} onPress={() => openSection(section.route)} style={{ minWidth: '45%' }}>
              <QuickAction label={section.label} icon={section.icon} onPress={() => openSection(section.route)} />
            </Pressable>
          ))}
        </View>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
