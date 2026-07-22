import { useCan, useOperationsSummary } from '@erp/core';
import {
  DashboardHero,
  DashboardSection,
  EmptyState,
  KpiCard,
  QuickAction,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'OperationsDashboard'>;

const TRANSPORT_SECTIONS = [
  { route: 'TripsList' as const, label: 'Routes & trips', icon: 'bus-outline' as const },
  { route: 'VehiclesList' as const, label: 'Vehicles', icon: 'car-sport-outline' as const },
  { route: 'TeacherTransport' as const, label: 'Teacher transport', icon: 'people-outline' as const },
  { route: 'DriverTrips' as const, label: 'Driver trips', icon: 'car-outline' as const },
];

const LOGISTICS_SECTIONS = [
  { route: 'InventoryList' as const, label: 'Inventory', icon: 'cube-outline' as const },
  { route: 'RequisitionsList' as const, label: 'Requisitions', icon: 'clipboard-outline' as const },
  { route: 'AssetsList' as const, label: 'Assets', icon: 'hardware-chip-outline' as const },
  { route: 'LibraryBooks' as const, label: 'Library', icon: 'library-outline' as const },
];

const FRONT_DESK_SECTIONS = [
  { route: 'VisitorsList' as const, label: 'Visitors', icon: 'person-outline' as const },
  { route: 'VisitorCheckIn' as const, label: 'Check in visitor', icon: 'log-in-outline' as const },
  { route: 'RequirementsRoster' as const, label: 'Requirements', icon: 'checkbox-outline' as const },
  { route: 'ConcernsList' as const, label: 'Concerns', icon: 'alert-circle-outline' as const },
];

export const OperationsDashboardScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { palette, spacing } = useTheme();
  const summaryQuery = useOperationsSummary({ enabled: canView });

  const openSection = useCallback(
    (route: keyof OperationsStackParamList) =>
      navigation.navigate(route as never),
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need operations.view permission to open this workspace."
          icon="lock-closed-outline"
        />
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
            colors={[palette.primary]}
            tintColor={palette.primary}
          />
        }
      >
        <DashboardHero
          variant="operations"
          title="Operations"
          subtitle="Transport, logistics & front desk"
          meta={
            s
              ? `${s.transport.active_trips} active trips · ${s.visitors?.on_site ?? 0} visitors on site`
              : undefined
          }
        />

        {summaryQuery.isError ? (
          <EmptyState
            title="Could not load dashboard"
            message={(summaryQuery.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void summaryQuery.refetch()}
          />
        ) : null}

        <WidgetGrid>
          {[
            { label: 'Active trips', value: String(s?.transport.active_trips ?? '—'), icon: 'bus-outline' as const },
            { label: 'Students on transport', value: String(s?.transport.students_assigned ?? '—'), icon: 'people-outline' as const },
            { label: 'Visitors on site', value: String(s?.visitors?.on_site ?? '—'), icon: 'walk-outline' as const },
            { label: 'Low stock items', value: String(s?.inventory.low_stock_items ?? '—'), icon: 'warning-outline' as const },
            { label: 'Active assets', value: String(s?.assets?.active ?? '—'), icon: 'hardware-chip-outline' as const },
            { label: 'Library books', value: String(s?.library.total_books ?? '—'), icon: 'library-outline' as const },
          ].map((kpi) => (
            <WidgetShell key={kpi.label} state={state} title={kpi.label} onRetry={() => void summaryQuery.refetch()}>
              <KpiCard label={kpi.label} value={kpi.value} icon={kpi.icon} />
            </WidgetShell>
          ))}
        </WidgetGrid>

        <DashboardSection title="Transport">
          <View style={[styles.grid, { gap: spacing.mdSm }]}>
            {TRANSPORT_SECTIONS.map((section) => (
              <QuickAction
                key={section.route}
                label={section.label}
                icon={section.icon}
                onPress={() => openSection(section.route)}
              />
            ))}
          </View>
        </DashboardSection>

        <DashboardSection title="Logistics">
          <View style={[styles.grid, { gap: spacing.mdSm }]}>
            {LOGISTICS_SECTIONS.map((section) => (
              <QuickAction
                key={section.route}
                label={section.label}
                icon={section.icon}
                onPress={() => openSection(section.route)}
              />
            ))}
          </View>
        </DashboardSection>

        <DashboardSection title="Front desk & students">
          <View style={[styles.grid, { gap: spacing.mdSm }]}>
            {FRONT_DESK_SECTIONS.map((section) => (
              <QuickAction
                key={section.route}
                label={section.label}
                icon={section.icon}
                onPress={() => openSection(section.route)}
              />
            ))}
          </View>
        </DashboardSection>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  grid: { flexDirection: 'row', flexWrap: 'wrap' },
});
