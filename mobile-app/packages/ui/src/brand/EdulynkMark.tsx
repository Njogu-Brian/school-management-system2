import React from 'react';
import { Image, StyleSheet, View, type ImageStyle, type StyleProp, type ViewStyle } from 'react-native';
import lockup from './edulynk-logo.png';
import mark from './edulynk-mark.png';

export type EdulynkMarkVariant = 'mark' | 'lockup';

/**
 * Official Edulynk branding.
 * - `mark`: icon only (e + graduates) — app chrome / compact slots
 * - `lockup`: full wordmark + tagline — school code / marketing surfaces
 */
export const EdulynkMark: React.FC<{
  size?: number;
  /** @deprecated ignored — logo assets are color-correct for light and dark. */
  variant?: 'default' | 'white' | EdulynkMarkVariant;
  style?: StyleProp<ViewStyle>;
}> = ({ size = 40, variant = 'mark', style }) => {
  const isLockup = variant === 'lockup';
  const source = isLockup ? lockup : mark;

  if (isLockup) {
    const width = Math.max(size * 2.6, 200);
    const height = Math.round(width * 0.42);
    return (
      <View style={[{ width, height, alignItems: 'center', justifyContent: 'center' }, style]}>
        <Image
          source={source}
          style={{ width, height } as ImageStyle}
          resizeMode="contain"
          accessibilityIgnoresInvertColors
          accessibilityLabel="Edulynk"
        />
      </View>
    );
  }

  return (
    <View
      style={[
        {
          width: size,
          height: size,
          borderRadius: Math.round(size * 0.22),
          overflow: 'hidden',
          backgroundColor: '#000000',
        },
        style,
      ]}
    >
      <Image
        source={source}
        style={StyleSheet.absoluteFillObject}
        resizeMode="cover"
        accessibilityIgnoresInvertColors
        accessibilityLabel="Edulynk"
      />
    </View>
  );
};

/** Alias for the full horizontal lockup. */
export const EdulynkLogo: React.FC<{ width?: number; style?: StyleProp<ViewStyle> }> = ({
  width = 240,
  style,
}) => <EdulynkMark size={Math.round(width / 2.6)} variant="lockup" style={style} />;
