import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  /** Opens profile when the top-right avatar is pressed. */
  onProfilePress?: () => void;
  rightSlot?: React.ReactNode;
  style?: ViewStyle;
}

/**
 * Shared stack/screen header (V3). Prefer this over Unicode back arrows
 * or one-off domain headers.
 */
export const ScreenHeader: React.FC<ScreenHeaderProps> = ({
  title,
  subtitle,
  onBack,
  onProfilePress,
  rightSlot,
  style,
}) => {
  const { palette, spacing, typography, colors } = useTheme();

  const profileSlot = onProfilePress ? (
    <Pressable
      onPress={onProfilePress}
      hitSlop={8}
      accessibilityRole="button"
      accessibilityLabel="Open profile"
      style={styles.profileBtn}
    >
      <View
        style={[
          styles.profileAvatar,
          { backgroundColor: palette.primary },
        ]}
      >
        <Ionicons name="person" size={16} color={palette.textOnPrimary ?? colors.primaryOnDark ?? '#fff'} />
      </View>
    </Pressable>
  ) : null;

  return (
    <View style={[styles.row, { marginBottom: spacing.md }, style]}>
      {onBack ? (
        <Pressable
          onPress={onBack}
          hitSlop={12}
          accessibilityRole="button"
          accessibilityLabel="Go back"
          style={[styles.backBtn, { marginRight: spacing.sm }]}
        >
          <Ionicons name="arrow-back" size={22} color={palette.textMain} />
        </Pressable>
      ) : null}
      <View style={{ flex: 1 }}>
        <Text
          numberOfLines={1}
          style={{
            color: palette.textMain,
            fontSize: typography.title.fontSize,
            lineHeight: typography.title.lineHeight,
            fontWeight: typography.title.fontWeight,
          }}
        >
          {title}
        </Text>
        {subtitle ? (
          <Text
            numberOfLines={2}
            style={{
              color: palette.textSub,
              fontSize: typography.caption.fontSize,
              lineHeight: typography.caption.lineHeight,
              marginTop: 2,
            }}
          >
            {subtitle}
          </Text>
        ) : null}
      </View>
      {rightSlot ? <View style={styles.right}>{rightSlot}</View> : null}
      {profileSlot}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
  backBtn: {
    minWidth: 44,
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  right: { marginLeft: 8 },
  profileBtn: {
    marginLeft: 8,
    minWidth: 40,
    minHeight: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
