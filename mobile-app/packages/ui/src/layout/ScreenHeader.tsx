import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
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
  rightSlot,
  style,
}) => {
  const { palette, spacing, typography } = useTheme();

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
});
