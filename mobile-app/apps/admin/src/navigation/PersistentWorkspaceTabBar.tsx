import { Ionicons } from '@expo/vector-icons';
import type { AdminAreaKey } from '@erp/core';
import { useRbac } from '@erp/core';
import { PremiumTabBar } from '@erp/ui';
import { CommonActions, useNavigation } from '@react-navigation/native';
import React, { useCallback, useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import type { TabsParamList } from './types';

const TAB_ICON: Record<keyof TabsParamList, keyof typeof Ionicons.glyphMap> = {
  Dashboard: 'grid-outline',
  Students: 'people-outline',
  Finance: 'cash-outline',
  People: 'briefcase-outline',
};

const TAB_ICON_FOCUSED: Record<keyof TabsParamList, keyof typeof Ionicons.glyphMap> = {
  Dashboard: 'grid',
  Students: 'people',
  Finance: 'cash',
  People: 'briefcase',
};

const TAB_BAR_LABEL: Record<keyof TabsParamList, string> = {
  Dashboard: 'Home',
  Students: 'Students',
  Finance: 'Finance',
  People: 'HR',
};

const TAB_AREA_KEY: Record<keyof TabsParamList, AdminAreaKey> = {
  Dashboard: 'dashboard',
  Students: 'students',
  Finance: 'finance',
  People: 'people',
};

const TAB_TONE: Record<keyof TabsParamList, 'blue' | 'indigo' | 'emerald' | 'cyan'> = {
  Dashboard: 'blue',
  Students: 'indigo',
  Finance: 'emerald',
  People: 'cyan',
};

/** Floating tabs for drawer-only routes (Approvals, Academics, Settings, …). */
export const DrawerWorkspaceTabBar: React.FC = () => {
  const navigation = useNavigation();
  const { tabAreas } = useRbac();

  const allowedRoutes = useMemo(
    () =>
      tabAreas
        .map((a) => {
          const entry = Object.entries(TAB_AREA_KEY).find(([, key]) => key === a.key);
          return entry ? (entry[0] as keyof TabsParamList) : null;
        })
        .filter((r): r is keyof TabsParamList => r != null),
    [tabAreas],
  );

  const items = useMemo(
    () =>
      allowedRoutes.map((name) => ({
        key: name,
        label: TAB_BAR_LABEL[name],
        icon: TAB_ICON[name],
        iconFocused: TAB_ICON_FOCUSED[name],
        tone: TAB_TONE[name],
      })),
    [allowedRoutes],
  );

  const onTabPress = useCallback(
    (key: string) => {
      navigation.dispatch(
        CommonActions.navigate({
          name: 'Workspace',
          params: { screen: key },
        }),
      );
    },
    [navigation],
  );

  if (items.length === 0) return null;

  return <PremiumTabBar items={items} activeKey="" onTabPress={onTabPress} />;
};

/** Wrap a drawer stack so the floating workspace tab bar remains visible. */
export function withWorkspaceTabBar<P extends object>(
  Component: React.ComponentType<P>,
): React.FC<P> {
  const Wrapped: React.FC<P> = (props) => (
    <View style={styles.fill}>
      <Component {...props} />
      <DrawerWorkspaceTabBar />
    </View>
  );
  Wrapped.displayName = `withWorkspaceTabBar(${Component.displayName ?? Component.name ?? 'Screen'})`;
  return Wrapped;
}

const styles = StyleSheet.create({
  fill: { flex: 1 },
});
