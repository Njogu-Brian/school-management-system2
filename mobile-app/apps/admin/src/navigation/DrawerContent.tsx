import { Ionicons } from '@expo/vector-icons';
import {
  AdminAreaKey,
  AdminNavArea,
  useAuth,
  useBranding,
  useCurrentUser,
  useRbac,
} from '@erp/core';
import { Soft3DIcon, useTheme, type Soft3DTone } from '@erp/ui';
import { BlurView } from 'expo-blur';
import {
  DrawerContentComponentProps,
  DrawerContentScrollView,
} from '@react-navigation/drawer';
import React from 'react';
import { Image, Platform, Pressable, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
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

const AREA_TONE: Record<string, Soft3DTone> = {
  dashboard: 'blue',
  students: 'indigo',
  finance: 'emerald',
  people: 'cyan',
  approvals: 'violet',
  admissions: 'amber',
  academics: 'violet',
  operations: 'amber',
  communication: 'rose',
  reports: 'blue',
  settings: 'teal',
};

export const DrawerContent: React.FC<DrawerContentComponentProps> = (props) => {
  const { palette, colors, spacing, typography, radius, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const { width: windowWidth } = useWindowDimensions();
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

  const frosted = (
    <BlurView
      intensity={Platform.OS === 'ios' ? 64 : 80}
      tint={isDark ? 'dark' : 'light'}
      experimentalBlurMethod={Platform.OS === 'android' ? 'dimezisBlurView' : undefined}
      style={StyleSheet.absoluteFill}
    />
  );

  return (
    <View style={[styles.root, { width: Math.min(280, windowWidth * 0.72) }]}>
      {frosted}
      <View
        style={[
          StyleSheet.absoluteFill,
          {
            backgroundColor: isDark ? 'rgba(12,16,24,0.28)' : 'rgba(0,74,153,0.06)',
          },
        ]}
      />
      <DrawerContentScrollView
        {...props}
        contentContainerStyle={[
          styles.content,
          {
            paddingTop: insets.top + spacing.sm,
            paddingBottom: insets.bottom + spacing.md,
          },
        ]}
        style={{ backgroundColor: 'transparent' }}
      >
        <View style={[styles.header, { borderBottomColor: palette.borderSubtle }]}>
          {logoUrl ? (
            <Image source={{ uri: logoUrl }} style={styles.logoImage} />
          ) : (
            <Soft3DIcon name="school" tone="blue" size={40} />
          )}
          <View style={[styles.headerText, { marginLeft: spacing.sm }]}>
            <Text
              style={{
                color: palette.textPrimary,
                fontSize: typography.titleSmall.fontSize,
                fontWeight: '700',
              }}
              numberOfLines={2}
            >
              {schoolName}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                fontWeight: '600',
                marginTop: 2,
              }}
            >
              Admin
            </Text>
          </View>
        </View>

        <View style={{ paddingVertical: spacing.sm }}>
          {drawerAreas.map((area) => {
            const active = area.key === activeKey;
            const tone = AREA_TONE[area.key] ?? 'blue';
            return (
              <Pressable
                key={area.key}
                accessibilityRole="button"
                accessibilityLabel={area.label}
                accessibilityState={{ selected: active }}
                onPress={() => handlePress(area)}
                style={[
                  styles.item,
                  {
                    borderRadius: radius.control,
                    backgroundColor: active ? palette.primaryMuted : 'transparent',
                  },
                ]}
              >
                <Soft3DIcon
                  name={area.icon as keyof typeof Ionicons.glyphMap}
                  tone={active ? tone : 'muted'}
                  muted={!active}
                  active={active}
                  size={36}
                />
                <Text
                  style={{
                    marginLeft: spacing.mdSm,
                    color: active ? palette.primary : palette.textPrimary,
                    fontSize: typography.body.fontSize,
                    fontWeight: active ? '700' : '500',
                    flex: 1,
                  }}
                >
                  {area.label}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <View style={[styles.footer, { borderTopColor: palette.borderSubtle }]}>
          {user ? (
            <View style={styles.userRow}>
              <View style={[styles.avatar, { backgroundColor: palette.primaryMuted }]}>
                <Text style={[styles.avatarText, { color: colors.primary }]}>
                  {user.name?.trim()?.charAt(0)?.toUpperCase() ?? '?'}
                </Text>
              </View>
              <View style={styles.userText}>
                <Text
                  numberOfLines={1}
                  style={{ color: palette.textPrimary, fontSize: typography.body.fontSize, fontWeight: '700' }}
                >
                  {user.name}
                </Text>
                {user.roleName ? (
                  <Text
                    numberOfLines={1}
                    style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 1 }}
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
            <Soft3DIcon name="log-out-outline" tone="rose" size={32} />
            <Text
              style={{
                marginLeft: spacing.mdSm,
                color: colors.error,
                fontSize: typography.titleSmall.fontSize,
                fontWeight: '600',
              }}
            >
              Sign out
            </Text>
          </Pressable>
        </View>
      </DrawerContentScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  root: { flex: 1, overflow: 'hidden' },
  content: { flexGrow: 1, paddingTop: 0, backgroundColor: 'transparent' },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  logoImage: {
    width: 40,
    height: 40,
    borderRadius: 12,
    resizeMode: 'contain',
  },
  headerText: { flex: 1 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 8,
    marginHorizontal: 8,
    marginVertical: 2,
    minHeight: 48,
  },
  footer: {
    marginTop: 'auto',
    paddingTop: 12,
    paddingHorizontal: 14,
    paddingBottom: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  userRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  avatar: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontWeight: '700', fontSize: 15 },
  userText: { marginLeft: 10, flex: 1 },
  logout: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
  },
});
