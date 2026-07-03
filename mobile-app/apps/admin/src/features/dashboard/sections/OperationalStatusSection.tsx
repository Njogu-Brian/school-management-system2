import { useCan, useOperationsSummary } from '@erp/core';
import { DashboardSection, KpiCard, WidgetGrid, WidgetShell } from '@erp/ui';
import React from 'react';

export const OperationalStatusSection: React.FC = () => {
  const canView = useCan('dashboard.view');
  const summaryQuery = useOperationsSummary({ enabled: canView });

  if (!canView) {
    return null;
  }

  const state = summaryQuery.isLoading
    ? 'loading'
    : summaryQuery.isError
      ? 'error'
      : summaryQuery.data
        ? 'success'
        : 'empty';
  const data = summaryQuery.data;

  return (
    <DashboardSection title="Operational status" subtitle="Live operations snapshot">
      <WidgetGrid>
        <WidgetShell state={state} title="Transport" onRetry={() => void summaryQuery.refetch()}>
          <KpiCard
            label="Transport"
            value={String(data?.transport.active_trips ?? 0)}
            delta={`${data?.transport.students_assigned ?? 0} students assigned`}
            icon="bus-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Library" onRetry={() => void summaryQuery.refetch()}>
          <KpiCard
            label="Library"
            value={String(data?.library.total_books ?? 0)}
            delta={`${data?.library.available_books ?? 0} copies available`}
            icon="book-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Inventory" onRetry={() => void summaryQuery.refetch()}>
          <KpiCard
            label="Inventory"
            value={String(data?.inventory.tracked_items ?? 0)}
            delta={`${data?.inventory.low_stock_items ?? 0} low stock`}
            deltaPositive={(data?.inventory.low_stock_items ?? 0) === 0}
            icon="cube-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Facilities" onRetry={() => void summaryQuery.refetch()}>
          <KpiCard
            label="Facilities"
            value={String(data?.facilities.open_tickets ?? 0)}
            delta={`${data?.visitors?.on_site ?? 0} visitors on site`}
            icon="construct-outline"
          />
        </WidgetShell>
      </WidgetGrid>
    </DashboardSection>
  );
};
