import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
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
  onApprovalsPress?: () => void;
  onProfilePress?: () => void;
  showApprovalsBadge?: boolean;
  showNotificationsBadge?: boolean;
}

/**
 * Persistent top chrome (IA §1): menu · title · branch switcher · search · notifications ·
 * approvals · profile. Presentational only — all actions are injected callbacks so the
 * design system stays navigation-agnostic. The shell wires no-op/drawer handlers.
 */
export const GlobalAppHeader: React.FC<GlobalAppHeaderProps> = ({
  title,
  onMenuPress,
  branchLabel = 'Main Branch',
  onBranchPress,
  onSearchPress,
  searchPrompt = 'Search anything…',
  onNotificationsPress,
  onApprovalsPress,
  onProfilePress,
  showApprovalsBadge = false,
  showNotificationsBadge = false,
}) => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <View
      style={[
        styles.wrap,
        {
          paddingTop: insets.top + spacing.sm,
          backgroundColor: palette.surface,
          borderBottomColor: palette.border,
        },
      ]}
    >
      <View style={styles.topRow}>
        <View style={styles.left}>
        {onMenuPress ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Open menu"
            hitSlop={8}
            onPress={onMenuPress}
            style={styles.iconBtn}
          >
            <Ionicons name="menu" size={24} color={palette.textPrimary} />
          </Pressable>
        ) : null}
        <View style={styles.titleBlock}>
          <Text
            numberOfLines={1}
            style={[styles.title, { color: palette.textPrimary, fontSize: typography.heading.fontSize }]}
          >
            {title}
          </Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Switch branch"
            onPress={onBranchPress}
            style={styles.branch}
          >
            <Ionicons name="business-outline" size={12} color={palette.textSecondary} />
            <Text
              numberOfLines={1}
              style={[styles.branchText, { color: palette.textSecondary, fontSize: typography.caption.fontSize }]}
            >
              {branchLabel}
            </Text>
            <Ionicons name="chevron-down" size={12} color={palette.textSecondary} />
          </Pressable>
        </View>
        </View>

        <View style={styles.right}>
        <HeaderIcon name="search" color={palette.textPrimary} onPress={onSearchPress} label="Search" />
        <HeaderIcon
          name="checkmark-done-outline"
          color={palette.textPrimary}
          onPress={onApprovalsPress}
          label="Approvals"
          dotColor={showApprovalsBadge ? colors.warning : undefined}
        />
        <HeaderIcon
          name="notifications-outline"
          color={palette.textPrimary}
          onPress={onNotificationsPress}
          label="Notifications"
          dotColor={showNotificationsBadge ? colors.error : undefined}
        />
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Profile"
          hitSlop={8}
          onPress={onProfilePress}
          style={[styles.avatar, { backgroundColor: colors.primary }]}
        >
          <Ionicons name="person" size={16} color={colors.white} />
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
            {
              marginHorizontal: spacing.sm,
              marginTop: spacing.xs,
              marginBottom: spacing.sm,
              backgroundColor: palette.surfaceRaised,
              borderColor: palette.borderSubtle,
              borderRadius: radius.control,
            },
          ]}
        >
          <Ionicons name="search-outline" size={18} color={palette.textMuted} />
          <Text
            numberOfLines={1}
            style={{
              flex: 1,
              color: palette.textMuted,
              fontSize: typography.body.fontSize,
            }}
          >
            {searchPrompt}
          </Text>
        </Pressable>
      ) : null}
    </View>
  );
};

interface HeaderIconProps {
  name: keyof typeof Ionicons.glyphMap;
  color: string;
  label: string;
  onPress?: () => void;
  dotColor?: string;
}

const HeaderIcon: React.FC<HeaderIconProps> = ({ name, color, label, onPress, dotColor }) => (
  <Pressable
    accessibilityRole="button"
    accessibilityLabel={label}
    hitSlop={8}
    onPress={onPress}
    style={styles.iconBtn}
  >
    <Ionicons name={name} size={22} color={color} />
    {dotColor ? <View style={[styles.dot, { backgroundColor: dotColor }]} /> : null}
  </Pressable>
);

const styles = StyleSheet.create({
  wrap: {
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 12,
    paddingBottom: 4,
  },
  left: { flexDirection: 'row', alignItems: 'center', flexShrink: 1 },
  titleBlock: { marginLeft: 4, flexShrink: 1 },
  title: { fontWeight: '700' },
  branch: { flexDirection: 'row', alignItems: 'center', marginTop: 1 },
  branchText: { marginHorizontal: 3, maxWidth: 160 },
  right: { flexDirection: 'row', alignItems: 'center' },
  iconBtn: { padding: 6, marginLeft: 2 },
  dot: {
    position: 'absolute',
    top: 4,
    right: 4,
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
    marginLeft: 6,
  },
  searchPrompt: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 14,
    paddingVertical: 12,
    minHeight: 44,
  },
});
