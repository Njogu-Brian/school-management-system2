import { DashboardSection, WidgetGrid } from '@erp/ui';
import React from 'react';
import { useVisibleDashboardWidgets } from '../hooks/useDashboardWidgets';
import { WIDGET_COMPONENTS } from '../widgets/widgetMap';

/** Permission-filtered KPI grid (Enrollment, Attendance, Finance, Approvals). */
export const CriticalKpisSection: React.FC = () => {
  const visible = useVisibleDashboardWidgets();

  if (visible.length === 0) {
    return null;
  }

  return (
    <DashboardSection
      title="Critical KPIs"
      subtitle="Branch-scoped snapshot — placeholder data"
    >
      <WidgetGrid>
        {visible.map((def) => {
          const Widget = WIDGET_COMPONENTS[def.id];
          return <Widget key={def.id} />;
        })}
      </WidgetGrid>
    </DashboardSection>
  );
};
