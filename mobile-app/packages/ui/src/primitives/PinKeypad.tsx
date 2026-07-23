import React from 'react';
import { Pressable, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', '⌫'] as const;

export interface PinKeypadProps {
  onKey: (key: string) => void;
  disabled?: boolean;
  /** Dark translucent keys for login chrome */
  variant?: 'default' | 'onDark';
}

/**
 * 3-column PIN pad with equal square keys (avoids wrap/gap misalignment).
 */
export const PinKeypad: React.FC<PinKeypadProps> = ({ onKey, disabled = false, variant = 'default' }) => {
  const { palette, radius, spacing } = useTheme();
  const { width } = useWindowDimensions();
  const padWidth = Math.min(320, width - spacing.lg * 2);
  const gap = 10;
  const keySize = Math.floor((padWidth - gap * 2) / 3);

  const onDark = variant === 'onDark';

  return (
    <View style={[styles.pad, { width: padWidth, gap }]}>
      {KEYS.map((key, idx) => {
        const empty = !key;
        return (
          <Pressable
            key={`${key}-${idx}`}
            onPress={() => {
              if (!empty && !disabled) onKey(key);
            }}
            disabled={empty || disabled}
            style={[
              styles.key,
              {
                width: keySize,
                height: keySize,
                borderRadius: radius.lg,
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
            <Text
              style={{
                color: onDark ? '#fff' : palette.textPrimary,
                fontSize: 22,
                fontWeight: '600',
                textAlign: 'center',
                textAlignVertical: 'center',
                includeFontPadding: false,
                lineHeight: keySize,
                width: '100%',
                height: keySize,
              }}
            >
              {key}
            </Text>
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
