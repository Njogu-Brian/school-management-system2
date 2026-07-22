import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, View, ViewStyle } from 'react-native';
import {
  resolveSoft3DGlyph,
  Soft3DGlyph,
  type Soft3DGlyphKey,
} from './Soft3DGlyphs';

export type { Soft3DGlyphKey };

/** Kept for callers that still pass tone — ignored for fill; glyphs own their colors. */
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
  /** Soft-3D glyph key. If omitted, resolved from `name`. */
  glyph?: Soft3DGlyphKey;
  /** Ionicons name (also used for glyph resolution). */
  name?: keyof typeof Ionicons.glyphMap;
  /** Ignored for fill — glyphs use their own color schemes (KCB-style). Kept for API compat. */
  tone?: Soft3DTone;
  size?: number;
  /** @deprecated Soft-3D glyphs size themselves; kept for API compat. */
  iconSize?: number;
  /** Dim inactive nav icons. */
  muted?: boolean;
  /** Active tab lift. */
  active?: boolean;
  style?: ViewStyle;
}

/**
 * Soft-3D illustration icon — colorful volumetric glyph with NO colored square/circle well.
 * Sits directly on the parent surface (flagship banking shortcut style).
 */
export const Soft3DIcon: React.FC<Soft3DIconProps> = ({
  glyph,
  name,
  size = 52,
  muted = false,
  active = false,
  style,
}) => {
  const glyphKey = resolveSoft3DGlyph(name, glyph);
  const renderSize = Math.round(size);

  return (
    <View
      style={[
        styles.wrap,
        {
          width: renderSize,
          height: renderSize,
          opacity: muted ? 0.5 : 1,
          // Always pass an array — `undefined` crashes RN processTransform (forEach of null)
          transform: active ? [{ translateY: -1 }, { scale: 1.06 }] : [{ translateY: 0 }, { scale: 1 }],
        },
        style,
      ]}
    >
      <Soft3DGlyph glyph={glyphKey} size={renderSize} muted={muted} />
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
