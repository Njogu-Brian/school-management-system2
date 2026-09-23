import type { AdminAreaKey, AdminNavArea } from '@erp/core';
import { useRbac } from '@erp/core';
import { DashboardSection, QuickAction } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback, useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import { useTheme } from '@erp/ui';
import {
  AREA_TO_DRAWER_ROUTE,
  AREA_TO_TAB_ROUTE,
  DRAWER_HOME_SCREEN,
  TAB_HOME_SCREEN,
} from '../../../navigation/areaRoutes';
import { navigateToDrawer, navigateToTab } from '../../../navigation/navigateWorkspace';

/** Home tiles for every module formerly listed in the side drawer (except Dashboard). */
export const ModulesSection: React.FC = () => {
  const { drawerAreas } = useRbac();
  const { spacing } = useTheme();
  const navigation = useNavigation();

  const modules = useMemo(
    () => drawerAreas.filter((area) => area.key !== 'dashboard'),
    [drawerAreas],
  );

  const onModulePress = useCallback(
    (area: AdminNavArea) => {
      const key = area.key as AdminAreaKey;
      const tabRoute = AREA_TO_TAB_ROUTE[key];
      if (tabRoute) {
        navigateToTab(navigation as never, tabRoute, TAB_HOME_SCREEN[tabRoute]);
        return;
      }
      const drawerRoute = AREA_TO_DRAWER_ROUTE[key];
      if (drawerRoute) {
        navigateToDrawer(
          navigation as never,
          drawerRoute,
          DRAWER_HOME_SCREEN[drawerRoute],
        );
      }
    },
    [navigation],
  );

  if (modules.length === 0) {
    return null;
  }

  return (
    <DashboardSection title="Modules" subtitle="Open any workspace from here">
      <View style={[styles.row, { gap: spacing.sm }]}>
        {modules.map((area) => (
          <QuickAction
            key={area.key}
            label={area.label}
            icon={area.icon}
            onPress={() => onModulePress(area)}
          />
        ))}
      </View>
    </DashboardSection>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
});
