import React from 'react';
import { View, type StyleProp, type ViewStyle } from 'react-native';
import { resolveAppIconName, type AppIconName } from './names';
import { SheetIcon } from './sheetIcons';

export interface AppIconProps {
  /** Canonical name, Ionicons name, or Soft3D key. */
  name: AppIconName | string;
  size?: number;
  /** Stroke color. Defaults to the active theme primary. */
  color?: string;
  strokeWidth?: number;
  style?: StyleProp<ViewStyle>;
  /** When true (default), hide from accessibility tree — parent should label the control. */
  decorative?: boolean;
  accessibilityLabel?: string;
}

/**
 * Shared SVG icon from the Royal Kings sheet.
 * Each name keeps its own colors. The `color` prop is ignored so icons are not tinted to the theme purple.
 */
export const AppIcon: React.FC<AppIconProps> = ({
  name,
  size = 24,
  style,
  decorative = true,
  accessibilityLabel,
}) => {
  const resolved = resolveAppIconName(name);

  return (
    <View
      style={style}
      accessible={!decorative}
      accessibilityElementsHidden={decorative}
      importantForAccessibility={decorative ? 'no-hide-descendants' : 'yes'}
      accessibilityLabel={decorative ? undefined : accessibilityLabel}
      accessibilityRole={decorative ? undefined : 'image'}
    >
      <SheetIcon name={resolved} size={size} />
    </View>
  );
};

export type { AppIconName };
