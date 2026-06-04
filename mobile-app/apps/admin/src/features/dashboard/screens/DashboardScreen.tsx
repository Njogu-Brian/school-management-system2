import { getNavArea, useRbac } from '@erp/core';
import { PlaceholderScreen } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';

const area = getNavArea('dashboard');

const TAB_LABELS: Record<string, string> = {
  overview: 'Overview',
  approvals: 'Approvals',
  alerts: 'Alerts',
};

/**
 * Dashboard shell with visibility rules only (Batch 3 — no widgets).
 * Surfaces which dashboard tabs the user may see per the permission engine.
 */
export const DashboardScreen: React.FC = () => {
  const { visibleDashboardTabs, canViewDashboardTab } = useRbac();

  const sections = useMemo(() => {
    const lines = [...area.sections];
    lines.push(
      `Visible tabs: ${
        visibleDashboardTabs.length
          ? visibleDashboardTabs.map((t) => TAB_LABELS[t] ?? t).join(' · ')
          : 'None'
      }`,
    );
    for (const tab of ['overview', 'approvals', 'alerts'] as const) {
      lines.push(`${canViewDashboardTab(tab) ? '✓' : '—'} ${TAB_LABELS[tab]} (gated)`);
    }
    return lines;
  }, [visibleDashboardTabs, canViewDashboardTab]);

  return (
    <PlaceholderScreen
      title={area.label}
      description={area.description}
      icon={area.icon as keyof typeof Ionicons.glyphMap}
      sections={sections}
    />
  );
};
