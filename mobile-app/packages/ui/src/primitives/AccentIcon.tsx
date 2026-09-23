import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, View, ViewStyle } from 'react-native';
import { SheetIcon } from '../icons/sheetIcons';
import { resolveAppIconName, type AppIconName } from '../icons/names';

export type { AppIconName as Soft3DGlyphKey };

/** Kept for callers that still pass tone — color now comes from the theme. */
export type Soft3DTone =
  | 'blue'
  | 'teal'
  | 'violet'
  | 'amber'
  | 'rose'
  | 'emerald'
  | 'cyan'
  | 'indigo'
  | 'muted';

/** @deprecated Prefer Soft3DTone — kept for AccentIcon callers. */
export type AccentTone = Soft3DTone;

export interface Soft3DIconProps {
  /** Canonical AppIcon name. If omitted, resolved from `name`. */
  glyph?: AppIconName | string;
  /** Ionicons name or canonical AppIcon name. */
  name?: keyof typeof Ionicons.glyphMap | AppIconName | string;
  /** Ignored for fill — icons inherit the theme primary. Kept for API compat. */
  tone?: Soft3DTone;
  size?: number;
  /** @deprecated Icons size themselves; kept for API compat. */
  iconSize?: number;
  /** Dim inactive nav icons. */
  muted?: boolean;
  /** Active tab lift. */
  active?: boolean;
  style?: ViewStyle;
  color?: string;
}

/**
 * Themed SVG icon used by hubs, drawer, KPIs, and empty states.
 * Delegates to the shared AppIcon registry so Admin, Users, and Edulynk match.
 */
export const Soft3DIcon: React.FC<Soft3DIconProps> = ({
  glyph,
  name,
  size = 52,
  active = false,
  style,
}) => {
  const iconName = resolveAppIconName(
    typeof name === 'string' ? name : undefined,
    typeof glyph === 'string' ? glyph : undefined,
  );
  const renderSize = Math.round(size);

  return (
    <View
      style={[
        styles.wrap,
        {
          width: renderSize,
          height: renderSize,
          transform: active ? [{ translateY: -1 }, { scale: 1.06 }] : [{ scale: 1 }],
        },
        style,
      ]}
    >
      <SheetIcon name={iconName} size={renderSize} />
    </View>
  );
};

/** Backward-compatible alias. */
export type AccentIconProps = Soft3DIconProps;

export const AccentIcon: React.FC<AccentIconProps> = (props) => <Soft3DIcon {...props} />;

const styles = StyleSheet.create({
  wrap: {
    alignItems: 'center',
    justifyContent: 'center',
  },
});
