import { useCan, useOperationsSummary } from '@erp/core';
import { KpiCard, QuickAction, ScreenContainer, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'OperationsDashboard'>;

const SECTIONS = [
  { route: 'TripsList' as const, label: 'Routes', icon: 'bus-outline' as const },
  { route: 'TeacherTransport' as const, label: 'Teacher transport', icon: 'people-outline' as const },
  { route: 'DriverTrips' as const, label: 'Driver trips', icon: 'car-outline' as const },
  { route: 'InventoryList' as const, label: 'Inventory', icon: 'cube-outline' as const },
  { route: 'RequisitionsList' as const, label: 'Requisitions', icon: 'clipboard-outline' as const },
  { route: 'VisitorsList' as const, label: 'Visitors', icon: 'person-outline' as const },
  { route: 'AssetsList' as const, label: 'Assets', icon: 'hardware-chip-outline' as const },
  { route: 'VisitorCheckIn' as const, label: 'Check in', icon: 'log-in-outline' as const },
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
  const state = summaryQuery.isLoading ? 'loading' : summaryQuery.isError ? 'error' : 'success';

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
            { label: 'Visitors on site', value: String(s?.visitors?.on_site ?? '—'), icon: 'walk-outline' as const },
            { label: 'Active assets', value: String(s?.assets?.active ?? '—'), icon: 'hardware-chip-outline' as const },
            { label: 'Low stock', value: String(s?.inventory.low_stock_items ?? '—'), icon: 'warning-outline' as const },
            { label: 'Open tickets', value: String(s?.facilities.open_tickets ?? '—'), icon: 'construct-outline' as const },
            { label: 'Active trips', value: String(s?.transport.active_trips ?? '—'), icon: 'bus-outline' as const },
            { label: 'Library books', value: String(s?.library.total_books ?? '—'), icon: 'library-outline' as const },
          ].map((kpi) => (
            <WidgetShell key={kpi.label} state={state} title={kpi.label} onRetry={() => void summaryQuery.refetch()}>
              <KpiCard label={kpi.label} value={kpi.value} icon={kpi.icon} />
            </WidgetShell>
          ))}
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
