import React from 'react';
import { Pressable, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', '⌫'] as const;

export interface PinKeypadProps {
  onKey: (key: string) => void;
  disabled?: boolean;
  /** Dark translucent keys for login chrome */
  variant?: 'default' | 'onDark';
  /** Compact keys for sheets / settings (default). Use `comfortable` only on full-screen PIN flows. */
  density?: 'compact' | 'comfortable';
}

/**
 * 3-column PIN pad with capped circular keys so it never fills the screen.
 */
export const PinKeypad: React.FC<PinKeypadProps> = ({
  onKey,
  disabled = false,
  variant = 'default',
  density = 'compact',
}) => {
  const { palette, spacing } = useTheme();
  const { width } = useWindowDimensions();
  const maxPad = density === 'comfortable' ? 280 : 240;
  const padWidth = Math.min(maxPad, width - spacing.lg * 2);
  const gap = density === 'comfortable' ? 12 : 10;
  const rawSize = Math.floor((padWidth - gap * 2) / 3);
  const keySize = Math.min(density === 'comfortable' ? 68 : 56, rawSize);
  const fontSize = density === 'comfortable' ? 22 : 20;

  const onDark = variant === 'onDark';

  return (
    <View style={[styles.pad, { width: keySize * 3 + gap * 2, gap }]}>
      {KEYS.map((key, idx) => {
        const empty = !key;
        return (
          <Pressable
            key={`${key}-${idx}`}
            accessibilityRole="button"
            accessibilityLabel={key === '⌫' ? 'Delete' : key || undefined}
            onPress={() => {
              if (!empty && !disabled) onKey(key);
            }}
            disabled={empty || disabled}
            style={[
              styles.key,
              {
                width: keySize,
                height: keySize,
                borderRadius: keySize / 2,
                backgroundColor: empty
                  ? 'transparent'
                  : onDark
                    ? 'rgba(255,255,255,0.1)'
                    : palette.surfaceRaised,
                borderColor: empty
                  ? 'transparent'
                  : onDark
                    ? 'rgba(255,255,255,0.22)'
                    : palette.borderSubtle,
                opacity: disabled && !empty ? 0.45 : 1,
              },
            ]}
          >
            {!empty ? (
              <Text
                style={{
                  color: onDark ? '#fff' : palette.textPrimary,
                  fontSize,
                  fontWeight: '600',
                  textAlign: 'center',
                  includeFontPadding: false,
                }}
              >
                {key}
              </Text>
            ) : null}
          </Pressable>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  pad: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'flex-start',
    alignSelf: 'center',
  },
  key: {
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
