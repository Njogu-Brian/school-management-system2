import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Soft3DIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';

export interface GlobalAppHeaderProps {
  title: string;
  /** Opens the drawer. When omitted the menu button is hidden (e.g. nested stacks). */
  onMenuPress?: () => void;
  /** Static label for the active branch (no switching logic in the shell batch). */
  branchLabel?: string;
  onBranchPress?: () => void;
  /** When set, shows a tappable search affordance below the title row (opens global search). */
  searchPrompt?: string;
  onSearchPress?: () => void;
  onNotificationsPress?: () => void;
  /** Dark / light mode toggle (replaces the former approvals shortcut). */
  onThemeTogglePress?: () => void;
  onProfilePress?: () => void;
  showNotificationsBadge?: boolean;
}

/**
 * Persistent top chrome — brand gradient strip, soft surface, soft-3D action icons.
 */
export const GlobalAppHeader: React.FC<GlobalAppHeaderProps> = ({
  title,
  onMenuPress,
  branchLabel = 'Main Branch',
  onBranchPress,
  onSearchPress,
  searchPrompt = 'Search anything…',
  onNotificationsPress,
  onThemeTogglePress,
  onProfilePress,
  showNotificationsBadge = false,
}) => {
  const { palette, colors, spacing, typography, radius, elevation, isDark } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <View style={styles.root}>
      <LinearGradient
        colors={[colors.primaryLight, palette.primary]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={{ height: 3 }}
      />
      <View
        style={[
          styles.wrap,
          elevation[2],
          {
            paddingTop: insets.top + spacing.sm,
            backgroundColor: isDark ? palette.surfaceRaised : palette.surface,
            borderBottomColor: palette.borderSubtle,
          },
        ]}
      >
        <View style={[styles.topRow, { paddingHorizontal: spacing.mdSm }]}>
          <View style={styles.left}>
            {onMenuPress ? (
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Open menu"
                hitSlop={8}
                onPress={onMenuPress}
                style={[
                  styles.menuWell,
                  {
                    backgroundColor: isDark ? 'rgba(75,159,255,0.12)' : palette.primaryMuted,
                    borderRadius: radius.md,
                  },
                ]}
              >
                <Ionicons name="menu" size={22} color={palette.primary} />
              </Pressable>
            ) : null}
            <View style={[styles.titleBlock, { marginLeft: spacing.sm }]}>
              <Text
                numberOfLines={1}
                style={{
                  color: palette.textMain,
                  fontSize: typography.title.fontSize,
                  lineHeight: typography.title.lineHeight,
                  fontWeight: '800',
                  letterSpacing: -0.3,
                }}
              >
                {title}
              </Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Switch branch"
                onPress={onBranchPress}
                style={[
                  styles.branch,
                  {
                    backgroundColor: isDark ? 'rgba(75,159,255,0.14)' : palette.primaryMuted,
                    borderRadius: radius.full,
                    paddingHorizontal: spacing.sm,
                    paddingVertical: 3,
                    marginTop: spacing.xs,
                  },
                ]}
              >
                <Ionicons name="business-outline" size={12} color={palette.primary} />
                <Text
                  numberOfLines={1}
                  style={{
                    color: palette.primary,
                    fontSize: typography.caption.fontSize,
                    marginHorizontal: 3,
                    maxWidth: 160,
                    fontWeight: '600',
                  }}
                >
                  {branchLabel}
                </Text>
                <Ionicons name="chevron-down" size={12} color={palette.primary} />
              </Pressable>
            </View>
          </View>

          <View style={[styles.right, { alignSelf: 'center' }]}>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
              hitSlop={8}
              onPress={onThemeTogglePress}
              style={styles.iconWell}
            >
              <Ionicons
                name={isDark ? 'sunny-outline' : 'moon-outline'}
                size={22}
                color={palette.primary}
              />
            </Pressable>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Notifications"
              hitSlop={8}
              onPress={onNotificationsPress}
              style={styles.iconWell}
            >
              <Soft3DIcon name="notifications-outline" size={28} />
              {showNotificationsBadge ? (
                <View style={[styles.dot, { backgroundColor: colors.error }]} />
              ) : null}
            </Pressable>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Profile"
              hitSlop={8}
              onPress={onProfilePress}
              style={styles.avatarWell}
            >
              <LinearGradient
                colors={[palette.primary, colors.primaryLight]}
                style={styles.avatar}
              >
                <Ionicons name="person" size={16} color={palette.textOnPrimary} />
              </LinearGradient>
            </Pressable>
          </View>
        </View>

        {onSearchPress ? (
          <Pressable
            onPress={onSearchPress}
            accessibilityRole="search"
            accessibilityLabel={searchPrompt}
            style={[
              styles.searchPrompt,
              elevation[1],
              {
                marginHorizontal: spacing.md,
                marginTop: spacing.xs,
                marginBottom: spacing.sm,
                backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : palette.surfaceMuted,
                borderColor: palette.borderSubtle,
                borderRadius: radius.control,
              },
            ]}
          >
            <Soft3DIcon name="search-outline" size={28} muted />
            <Text
              numberOfLines={1}
              style={{
                flex: 1,
                color: palette.textMuted,
                fontSize: typography.body.fontSize,
                fontWeight: '500',
              }}
            >
              {searchPrompt}
            </Text>
            <View
              style={[
                styles.searchHint,
                {
                  backgroundColor: isDark ? 'rgba(255,255,255,0.08)' : palette.surfaceRaised,
                  borderRadius: radius.sm,
                },
              ]}
            >
              <Text style={{ color: palette.textMuted, fontSize: 11, fontWeight: '600' }}>Go</Text>
            </View>
          </Pressable>
        ) : null}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  root: {},
  wrap: {
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 6,
  },
  left: { flexDirection: 'row', alignItems: 'center', flexShrink: 1, flex: 1 },
  titleBlock: { flexShrink: 1 },
  branch: { flexDirection: 'row', alignItems: 'center', alignSelf: 'flex-start' },
  right: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    height: 40,
  },
  menuWell: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWell: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 2,
  },
  avatarWell: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 2,
  },
  dot: {
    position: 'absolute',
    top: 6,
    right: 6,
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  avatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchPrompt: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 12,
    paddingVertical: 10,
    minHeight: 48,
  },
  searchHint: {
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
});
