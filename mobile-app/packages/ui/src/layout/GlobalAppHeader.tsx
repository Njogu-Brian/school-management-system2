import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
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
 * Persistent top chrome — brand gradient strip, soft surface, icon wells.
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
            backgroundColor: palette.surfaceRaised,
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
                  fontWeight: '700',
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

          <View style={styles.right}>
            <HeaderIcon
              name="checkmark-done-outline"
              color={palette.textMain}
              onPress={onApprovalsPress}
              label="Approvals"
              warningDot={showApprovalsBadge}
              mutedBg={isDark ? 'rgba(255,255,255,0.06)' : palette.surfaceMuted}
              radius={radius.md}
            />
            <HeaderIcon
              name="notifications-outline"
              color={palette.textMain}
              onPress={onNotificationsPress}
              label="Notifications"
              dangerDot={showNotificationsBadge}
              mutedBg={isDark ? 'rgba(255,255,255,0.06)' : palette.surfaceMuted}
              radius={radius.md}
            />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Profile"
              hitSlop={8}
              onPress={onProfilePress}
              style={{ marginLeft: 6 }}
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
    </View>
  );
};

interface HeaderIconProps {
  name: keyof typeof Ionicons.glyphMap;
  color: string;
  label: string;
  onPress?: () => void;
  warningDot?: boolean;
  dangerDot?: boolean;
  mutedBg: string;
  radius: number;
}

const HeaderIcon: React.FC<HeaderIconProps> = ({
  name,
  color,
  label,
  onPress,
  warningDot,
  dangerDot,
  mutedBg,
  radius,
}) => {
  const { colors } = useTheme();
  const dot = warningDot ? colors.warning : dangerDot ? colors.error : undefined;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={8}
      onPress={onPress}
      style={[styles.iconWell, { backgroundColor: mutedBg, borderRadius: radius }]}
    >
      <Ionicons name={name} size={20} color={color} />
      {dot ? <View style={[styles.dot, { backgroundColor: dot }]} /> : null}
    </Pressable>
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
  left: { flexDirection: 'row', alignItems: 'center', flexShrink: 1 },
  titleBlock: { flexShrink: 1 },
  branch: { flexDirection: 'row', alignItems: 'center', alignSelf: 'flex-start' },
  right: { flexDirection: 'row', alignItems: 'center' },
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
    marginLeft: 6,
  },
  dot: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  avatar: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchPrompt: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 14,
    paddingVertical: 12,
    minHeight: 48,
  },
});
