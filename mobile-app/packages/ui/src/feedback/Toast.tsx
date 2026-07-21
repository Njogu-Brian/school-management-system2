import React, { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react';
import { Animated, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../theme/ThemeContext';
import type { SemanticTone } from '../theme/tokens';

export type ToastTone = Extract<SemanticTone, 'success' | 'warning' | 'danger' | 'info' | 'brand'>;

export interface ToastOptions {
  message: string;
  tone?: ToastTone;
  durationMs?: number;
}

interface ToastContextValue {
  showToast: (options: ToastOptions | string) => void;
}

const ToastContext = createContext<ToastContextValue | undefined>(undefined);

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { palette, spacing, typography, radius, semantic, motion, zIndex } = useTheme();
  const insets = useSafeAreaInsets();
  const [message, setMessage] = useState<string | null>(null);
  const [tone, setTone] = useState<ToastTone>('brand');
  const opacity = useRef(new Animated.Value(0)).current;
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const hide = useCallback(() => {
    Animated.timing(opacity, {
      toValue: 0,
      duration: motion.duration.fast,
      useNativeDriver: true,
    }).start(() => setMessage(null));
  }, [motion.duration.fast, opacity]);

  const showToast = useCallback(
    (options: ToastOptions | string) => {
      const opts = typeof options === 'string' ? { message: options } : options;
      if (timer.current) clearTimeout(timer.current);
      setTone(opts.tone ?? 'brand');
      setMessage(opts.message);
      opacity.setValue(0);
      Animated.timing(opacity, {
        toValue: 1,
        duration: motion.duration.medium,
        useNativeDriver: true,
      }).start();
      timer.current = setTimeout(hide, opts.durationMs ?? 3200);
    },
    [hide, motion.duration.medium, opacity],
  );

  const value = useMemo(() => ({ showToast }), [showToast]);
  const toneColors = semantic[tone];

  return (
    <ToastContext.Provider value={value}>
      {children}
      {message ? (
        <Animated.View
          pointerEvents="none"
          style={[
            styles.toast,
            {
              opacity,
              bottom: insets.bottom + spacing.lg,
              backgroundColor: palette.surfaceRaised,
              borderColor: toneColors.border,
              borderRadius: radius.card,
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.mdSm,
              zIndex: zIndex.toast,
            },
          ]}
        >
          <View style={[styles.dot, { backgroundColor: toneColors.fg }]} />
          <Text
            style={{
              flex: 1,
              color: palette.textMain,
              fontSize: typography.body.fontSize,
              fontWeight: '500',
            }}
          >
            {message}
          </Text>
        </Animated.View>
      ) : null}
    </ToastContext.Provider>
  );
};

export function useToast(): ToastContextValue {
  const ctx = useContext(ToastContext);
  if (!ctx) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return ctx;
}

const styles = StyleSheet.create({
  toast: {
    position: 'absolute',
    left: 16,
    right: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: StyleSheet.hairlineWidth,
    elevation: 6,
    shadowColor: '#004A99',
    shadowOpacity: 0.12,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
  },
  dot: { width: 8, height: 8, borderRadius: 4 },
});
