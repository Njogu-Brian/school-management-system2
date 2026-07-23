import {
  getRememberedUsername,
  hasPinUnlockAvailable,
  PIN_MAX_LENGTH,
  PIN_MIN_LENGTH,
  useAuth,
} from '@erp/core';
import { Button, PinKeypad, ScreenContainer, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { showError } from '../../shared/utils/feedback';

type Props = {
  onUsePassword: () => void;
};

export const PinUnlockPanel: React.FC<Props> = ({ onUsePassword }) => {
  const { unlockWithPin, submitting } = useAuth();
  const { spacing, typography, colors } = useTheme();
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
    <View style={{ width: '100%', marginBottom: spacing.lg, alignItems: 'center' }}>
      <Ionicons name="keypad-outline" size={36} color={colors.primaryOnDark ?? colors.primary} />
      <Text
        style={{
          color: '#fff',
          fontWeight: '700',
          marginTop: spacing.sm,
          fontSize: typography.body.fontSize,
        }}
      >
        Unlock with PIN
      </Text>
      {username ? (
        <Text style={{ color: 'rgba(255,255,255,0.65)', marginTop: 4 }}>{username}</Text>
      ) : null}

      <View style={{ flexDirection: 'row', gap: 8, marginVertical: spacing.md }}>
        {dots.map((filled, i) => (
          <View
            key={i}
            style={{
              width: 10,
              height: 10,
              borderRadius: 5,
              backgroundColor: filled ? colors.primary : 'rgba(255,255,255,0.25)',
            }}
          />
        ))}
      </View>

      <PinKeypad onKey={onKey} disabled={submitting} variant="onDark" />

      <Button
        label="Unlock"
        onPress={() => void submit()}
        loading={submitting}
        disabled={pin.length < PIN_MIN_LENGTH || submitting}
        style={{ marginTop: spacing.md, alignSelf: 'stretch' }}
      />
      <Pressable onPress={onUsePassword} style={{ marginTop: spacing.sm, alignItems: 'center' }}>
        <Text style={{ color: 'rgba(255,255,255,0.7)', fontWeight: '600' }}>Use password instead</Text>
      </Pressable>
    </View>
  );
};

export const PinUnlockScreen: React.FC<{ onUsePassword: () => void }> = ({ onUsePassword }) => (
  <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={{ padding: 24, justifyContent: 'center' }}>
    <PinUnlockPanel onUsePassword={onUsePassword} />
  </ScreenContainer>
);
