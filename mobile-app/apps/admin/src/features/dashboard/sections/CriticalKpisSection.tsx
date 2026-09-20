import { DashboardSection, WidgetGrid } from '@erp/ui';
import React from 'react';
import { useVisibleDashboardWidgets } from '../hooks/useDashboardWidgets';
import { WIDGET_COMPONENTS } from '../widgets/widgetMap';

/** Permission-filtered KPI grid (population, attendance, finance). */
export const CriticalKpisSection: React.FC = () => {
  const visible = useVisibleDashboardWidgets();

  if (visible.length === 0) {
    return null;
  }

  return (
    <DashboardSection
      title="Population & attendance"
      subtitle="Tap a count to open today's list"
    >
      <WidgetGrid>
        {visible.map((def) => {
          const Widget = WIDGET_COMPONENTS[def.id as keyof typeof WIDGET_COMPONENTS];
          if (!Widget) return null;
          return <Widget key={def.id} />;
        })}
      </WidgetGrid>
    </DashboardSection>
  );
};
