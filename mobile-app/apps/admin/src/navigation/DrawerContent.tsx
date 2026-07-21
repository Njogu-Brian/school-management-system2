import { Ionicons } from '@expo/vector-icons';
import {
  AdminAreaKey,
  AdminNavArea,
  useAuth,
  useBranding,
  useCurrentUser,
  useRbac,
} from '@erp/core';
import { useTheme } from '@erp/ui';
import {
  DrawerContentComponentProps,
  DrawerContentScrollView,
} from '@react-navigation/drawer';
import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { confirmAction } from '../features/shared/utils/feedback';
import { navigateDrawerAreaHome } from './areaRoutes';
function getActiveKey(state: DrawerContentComponentProps['state']): AdminAreaKey {
  const current = state.routes[state.index];
  if (current.name === 'Workspace') {
    const nested = current.state;
    if (nested && typeof nested.index === 'number') {
      const tabName = nested.routes[nested.index]?.name ?? 'Dashboard';
      const map: Record<string, AdminAreaKey> = {
        Dashboard: 'dashboard',
        Students: 'students',
        Finance: 'finance',
        People: 'people',
      };
      return map[tabName] ?? 'dashboard';
    }
    return 'dashboard';
  }
  const map: Record<string, AdminAreaKey> = {
    Approvals: 'approvals',
    Admissions: 'admissions',
    Academics: 'academics',
    Operations: 'operations',
    Communication: 'communication',
    Reports: 'reports',
    Settings: 'settings',
  };
  return map[current.name] ?? 'dashboard';
}

export const DrawerContent: React.FC<DrawerContentComponentProps> = (props) => {
  const { palette, colors, spacing, typography } = useTheme();
  const insets = useSafeAreaInsets();
  const activeKey = getActiveKey(props.state);
  const { drawerAreas } = useRbac();
  const { schoolName, logoUrl } = useBranding();
  const user = useCurrentUser();
  const { logout } = useAuth();

  const confirmLogout = (): void => {
    confirmAction('Sign out', 'Are you sure you want to sign out?', 'Sign out', () => void logout(), true);
  };

  const handlePress = (area: AdminNavArea): void => {
    navigateDrawerAreaHome(props.navigation, area.key);
    props.navigation.closeDrawer();
  };

  return (
    <DrawerContentScrollView
      {...props}
      contentContainerStyle={[
        styles.content,
        {
          backgroundColor: palette.surface,
          paddingTop: insets.top + spacing.sm,
          paddingBottom: insets.bottom + spacing.md,
        },
      ]}
    >
      <View style={[styles.header, { borderBottomColor: palette.border }]}>
        {logoUrl ? (
          <Image source={{ uri: logoUrl }} style={styles.logoImage} />
        ) : (
          <View style={[styles.logo, { backgroundColor: colors.primary }]}>
            <Ionicons name="school" size={20} color={colors.white} />
          </View>
        )}
        <View style={styles.headerText}>
          <Text style={[styles.appName, { color: palette.textPrimary, fontSize: typography.titleSmall.fontSize }]}>
            {schoolName}
          </Text>
          <Text
            style={[styles.appRole, { color: palette.textSecondary, fontSize: typography.caption.fontSize }]}
          >
            Admin
          </Text>
        </View>
      </View>

      <View style={{ paddingVertical: spacing.sm }}>
        {drawerAreas.map((area) => {
          const active = area.key === activeKey;
          return (
            <Pressable
              key={area.key}
              accessibilityRole="button"
              accessibilityLabel={area.label}
              accessibilityState={{ selected: active }}
              onPress={() => handlePress(area)}
              style={[styles.item, active && { backgroundColor: palette.primaryMuted }]}
            >
              <Ionicons
                name={area.icon as keyof typeof Ionicons.glyphMap}
                size={22}
                color={active ? colors.primary : palette.textSecondary}
              />
              <Text
                style={[
                  styles.itemLabel,
                  {
                    color: active ? colors.primary : palette.textPrimary,
                    fontSize: typography.titleSmall.fontSize,
                    fontWeight: active ? '700' : '500',
                  },
                ]}
              >
                {area.label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      <View style={[styles.footer, { borderTopColor: palette.border }]}>
        {user ? (
          <View style={styles.userRow}>
            <View style={[styles.avatar, { backgroundColor: palette.accent }]}>
              <Text style={[styles.avatarText, { color: colors.primary }]}>
                {user.name?.trim()?.charAt(0)?.toUpperCase() ?? '?'}
              </Text>
            </View>
            <View style={styles.userText}>
              <Text
                numberOfLines={1}
                style={[styles.userName, { color: palette.textPrimary, fontSize: typography.body.fontSize }]}
              >
                {user.name}
              </Text>
              {user.roleName ? (
                <Text
                  numberOfLines={1}
                  style={[styles.userRole, { color: palette.textSecondary, fontSize: typography.caption.fontSize }]}
                >
                  {user.roleName}
                </Text>
              ) : null}
            </View>
          </View>
        ) : null}

        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Sign out"
          onPress={confirmLogout}
          style={styles.logout}
        >
          <Ionicons name="log-out-outline" size={22} color={colors.error} />
          <Text style={[styles.logoutLabel, { color: colors.error, fontSize: typography.titleSmall.fontSize }]}>
            Sign out
          </Text>
        </Pressable>
      </View>
    </DrawerContentScrollView>
  );
};

const styles = StyleSheet.create({
  content: { flexGrow: 1, paddingTop: 0 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 16,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  logo: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoImage: {
    width: 40,
    height: 40,
    borderRadius: 12,
    resizeMode: 'contain',
  },
  headerText: { marginLeft: 12 },
  appName: { fontWeight: '700' },
  appRole: { marginTop: 1, fontWeight: '600', letterSpacing: 0.5 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginHorizontal: 8,
    borderRadius: 12,
  },
  itemLabel: { marginLeft: 14 },
  footer: {
    marginTop: 'auto',
    paddingTop: 12,
    paddingHorizontal: 16,
    paddingBottom: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  userRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  avatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontWeight: '700', fontSize: 16 },
  userText: { marginLeft: 12, flex: 1 },
  userName: { fontWeight: '700' },
  userRole: { marginTop: 1, fontWeight: '500' },
  logout: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
  },
  logoutLabel: { marginLeft: 14, fontWeight: '600' },
});
