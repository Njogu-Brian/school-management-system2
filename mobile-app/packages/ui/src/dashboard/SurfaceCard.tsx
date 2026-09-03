import React from 'react';
import { Pressable, StyleSheet, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { SemanticTone } from '../theme/tokens';

export interface SurfaceCardProps {
  children: React.ReactNode;
  onPress?: () => void;
  accent?: SemanticTone;
  padded?: boolean;
  style?: ViewStyle;
  testID?: string;
}

/**
 * Raised card used across parent/user screens — shadow, soft border,
 * optional left accent. Keeps lists off the flat white/dark sheet.
 */
export const SurfaceCard: React.FC<SurfaceCardProps> = ({
  children,
  onPress,
  accent,
  padded = true,
  style,
  testID,
}) => {
  const { palette, spacing, radius, elevation, semantic } = useTheme();
  const tone = accent ? semantic[accent] : null;

  const card = (
    <View
      style={[
        styles.card,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: tone?.border ?? palette.borderSubtle,
          borderRadius: radius.card,
          padding: padded ? spacing.md : 0,
          borderLeftWidth: tone ? 4 : StyleSheet.hairlineWidth,
          borderLeftColor: tone?.fg ?? palette.borderSubtle,
        },
        style,
      ]}
    >
      {children}
    </View>
  );

  if (!onPress) return card;

  return (
    <Pressable
      testID={testID}
      accessibilityRole="button"
      onPress={onPress}
      style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1 }]}
    >
      {card}
    </Pressable>
  );
};

const styles = StyleSheet.create({
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    marginBottom: 12,
    overflow: 'hidden',
  },
});
