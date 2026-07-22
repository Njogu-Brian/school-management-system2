import {
  getRememberedUsername,
  hasPinUnlockAvailable,
  PIN_MAX_LENGTH,
  PIN_MIN_LENGTH,
  useAuth,
} from '@erp/core';
import { Button, ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { showError } from '../../shared/utils/feedback';

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', '⌫'] as const;

type Props = {
  onUsePassword: () => void;
};

export const PinUnlockPanel: React.FC<Props> = ({ onUsePassword }) => {
  const { unlockWithPin, submitting } = useAuth();
  const { palette, spacing, typography, colors, radius } = useTheme();
  const [pin, setPin] = useState('');
  const [username, setUsername] = useState<string | null>(null);
  const [available, setAvailable] = useState(false);

  useEffect(() => {
    void (async () => {
      setAvailable(await hasPinUnlockAvailable());
      setUsername(await getRememberedUsername());
    })();
  }, []);

  const onKey = (key: string) => {
    if (!key) return;
    if (key === '⌫') {
      setPin((v) => v.slice(0, -1));
      return;
    }
    setPin((v) => (v.length >= PIN_MAX_LENGTH ? v : v + key));
  };

  const submit = async () => {
    if (pin.length < PIN_MIN_LENGTH) return;
    try {
      await unlockWithPin(pin);
    } catch (err) {
      setPin('');
      showError('PIN unlock', err instanceof Error ? err.message : 'Unlock failed.');
    }
  };

  useEffect(() => {
    if (pin.length >= PIN_MIN_LENGTH && pin.length === PIN_MAX_LENGTH) {
      void submit();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pin]);

  const dots = useMemo(
    () => Array.from({ length: Math.max(pin.length, PIN_MIN_LENGTH) }, (_, i) => i < pin.length),
    [pin.length],
  );

  if (!available) return null;

  return (
    <View style={{ width: '100%', marginBottom: spacing.lg }}>
      <View style={{ alignItems: 'center', marginBottom: spacing.md }}>
        <Soft3DIcon name="keypad-outline" tone="indigo" size={48} />
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.sm, fontSize: typography.body.fontSize }}>
          Unlock with PIN
        </Text>
        {username ? (
          <Text style={{ color: palette.textSecondary, marginTop: 4 }}>{username}</Text>
        ) : null}
      </View>
      <View style={{ flexDirection: 'row', justifyContent: 'center', gap: 8, marginBottom: spacing.md }}>
        {dots.map((filled, i) => (
          <View
            key={i}
            style={{
              width: 10,
              height: 10,
              borderRadius: 5,
              backgroundColor: filled ? colors.primary : palette.border,
            }}
          />
        ))}
      </View>
      <View style={styles.pad}>
        {KEYS.map((key, idx) => (
          <Pressable
            key={`${key}-${idx}`}
            onPress={() => onKey(key)}
            disabled={!key || submitting}
            style={[
              styles.key,
              {
                backgroundColor: key ? palette.surface : 'transparent',
                borderColor: key ? palette.border : 'transparent',
                borderRadius: radius.md,
              },
            ]}
          >
            <Text style={{ color: palette.textPrimary, fontSize: 18, fontWeight: '600' }}>{key}</Text>
          </Pressable>
        ))}
      </View>
      <Button
        label="Unlock"
        onPress={() => void submit()}
        loading={submitting}
        disabled={pin.length < PIN_MIN_LENGTH}
        style={{ marginTop: spacing.md }}
      />
      <Button label="Use password instead" variant="ghost" onPress={onUsePassword} style={{ marginTop: spacing.xs }} />
    </View>
  );
};

/** Full-screen PIN unlock when preferred. */
export const PinUnlockScreen: React.FC<{ onUsePassword: () => void }> = ({ onUsePassword }) => (
  <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={{ padding: 24, justifyContent: 'center' }}>
    <PinUnlockPanel onUsePassword={onUsePassword} />
  </ScreenContainer>
);

const styles = StyleSheet.create({
  pad: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'center', gap: 8 },
  key: {
    width: '30%',
    aspectRatio: 1.6,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
