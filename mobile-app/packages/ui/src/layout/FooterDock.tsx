import React from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { useFloatingTabBarClearance } from './PremiumTabBar';

export interface FooterDockProps {
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}

/**
 * In-flow dock for primary actions (Submit, Confirm). Sits just above the
 * floating tab bar. Use inside ScreenContainer with clearFloatingTabBar={false}
 * so clearance is applied once, on this dock.
 */
export const FooterDock: React.FC<FooterDockProps> = ({ children, style }) => {
  const { palette, spacing } = useTheme();
  const pad = useFloatingTabBarClearance(false);

  return (
    <View
      style={[
        styles.dock,
        {
          paddingTop: spacing.sm,
          paddingBottom: pad,
          paddingHorizontal: spacing.md,
          backgroundColor: palette.background,
          borderTopColor: palette.borderSubtle,
        },
        style,
      ]}
    >
      {children}
    </View>
  );
};

const styles = StyleSheet.create({
  dock: {
    borderTopWidth: StyleSheet.hairlineWidth,
  },
});
