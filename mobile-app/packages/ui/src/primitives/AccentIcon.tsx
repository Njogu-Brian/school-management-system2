import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import { StyleSheet, View, ViewStyle } from 'react-native';
import { useOptionalTheme } from '../theme/ThemeContext';

export type AccentTone =
  | 'blue'
  | 'teal'
  | 'violet'
  | 'amber'
  | 'rose'
  | 'emerald'
  | 'cyan'
  | 'indigo';

const TONE_GRADIENTS: Record<AccentTone, readonly [string, string]> = {
  blue: ['#1a6bc4', '#004A99'],
  teal: ['#2dd4bf', '#0d9488'],
  violet: ['#a78bfa', '#7c3aed'],
  amber: ['#fbbf24', '#d97706'],
  rose: ['#fb7185', '#e11d48'],
  emerald: ['#34d399', '#059669'],
  cyan: ['#22d3ee', '#0891b2'],
  indigo: ['#818cf8', '#4f46e5'],
};

export interface AccentIconProps {
  name: keyof typeof Ionicons.glyphMap;
  tone?: AccentTone;
  size?: number;
  iconSize?: number;
  style?: ViewStyle;
}

/**
 * Premium gradient icon well — colorful like flagship banking shortcuts,
 * not flat monochrome Ionicons alone.
 */
export const AccentIcon: React.FC<AccentIconProps> = ({
  name,
  tone = 'blue',
  size = 52,
  iconSize = 24,
  style,
}) => {
  const theme = useOptionalTheme();
  const gradientColors =
    tone === 'blue' && theme
      ? ([theme.colors.primaryLight, theme.palette.primary] as const)
      : TONE_GRADIENTS[tone];

  return (
    <LinearGradient
      colors={[...gradientColors]}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={[
        styles.well,
        {
          width: size,
          height: size,
          borderRadius: size * 0.32,
        },
        style,
      ]}
    >
      <View style={styles.shine} />
      <Ionicons name={name} size={iconSize} color="#ffffff" />
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  well: {
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.28,
    shadowRadius: 8,
    elevation: 4,
  },
  shine: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255,255,255,0.12)',
    height: '45%',
  },
});
