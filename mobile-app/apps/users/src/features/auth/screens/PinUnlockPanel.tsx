import {
  getRememberedUsername,
  PIN_MAX_LENGTH,
  PIN_MIN_LENGTH,
  errorMessage,
  useAuth,
} from '@erp/core';
import { Button, PinKeypad, ScreenContainer, TextField, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { showError } from '../../shared/utils/feedback';

type Props = {
  onUsePassword: () => void;
  variant?: 'default' | 'onDark';
  hidePasswordLink?: boolean;
  identifier?: string;
  onIdentifierChange?: (value: string) => void;
};

export const PinUnlockPanel: React.FC<Props> = ({
  onUsePassword,
  variant = 'default',
  hidePasswordLink = false,
  identifier: identifierProp,
  onIdentifierChange,
}) => {
  const { unlockWithPin, submitting } = useAuth();
  const { spacing, typography, colors, palette } = useTheme();
  const [pin, setPin] = useState('');
  const [remembered, setRemembered] = useState<string | null>(null);
  const onDark = variant === 'onDark';
  const username = (identifierProp ?? remembered ?? '').trim();

  useEffect(() => {
    void getRememberedUsername().then(setRemembered);
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
      await unlockWithPin(pin, username || undefined);
    } catch (err) {
      setPin('');
      showError('PIN unlock', errorMessage(err, 'Unlock failed.'));
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

  return (
    <View style={{ width: '100%', alignItems: 'center' }}>
      <Ionicons name="keypad-outline" size={28} color={onDark ? '#93c5fd' : colors.primary} />
      <Text
        style={{
          color: onDark ? '#fff' : palette.textPrimary,
          fontWeight: '700',
          marginTop: spacing.sm,
          fontSize: typography.body.fontSize,
        }}
      >
        Enter PIN
      </Text>
      {username ? (
        <Text style={{ color: onDark ? 'rgba(255,255,255,0.65)' : palette.textSecondary, marginTop: 4 }}>
          {username}
        </Text>
      ) : (
        <View style={{ width: '100%', marginTop: spacing.md }}>
          <TextField
            label="Username, email or phone"
            value={identifierProp ?? ''}
            onChangeText={onIdentifierChange}
            autoCapitalize="none"
            keyboardType="email-address"
            autoComplete="username"
          />
        </View>
      )}

      <View style={{ flexDirection: 'row', gap: 8, marginVertical: spacing.md }}>
        {dots.map((filled, i) => (
          <View
            key={i}
            style={{
              width: 10,
              height: 10,
              borderRadius: 5,
              backgroundColor: filled
                ? onDark
                  ? '#4B9FFF'
                  : colors.primary
                : onDark
                  ? 'rgba(255,255,255,0.28)'
                  : palette.border,
            }}
          />
        ))}
      </View>

      <PinKeypad onKey={onKey} disabled={submitting} variant={variant} density="compact" />

      <Button
        label="Unlock"
        onPress={() => void submit()}
        loading={submitting}
        disabled={pin.length < PIN_MIN_LENGTH || submitting}
        style={{ marginTop: spacing.md, alignSelf: 'stretch' }}
      />
      {!hidePasswordLink ? (
        <Pressable onPress={onUsePassword} style={{ marginTop: spacing.sm, alignItems: 'center' }}>
          <Text style={{ color: onDark ? 'rgba(255,255,255,0.7)' : palette.textSecondary, fontWeight: '600' }}>
            Use password instead
          </Text>
        </Pressable>
      ) : null}
    </View>
  );
};

export const PinUnlockScreen: React.FC<{ onUsePassword: () => void }> = ({ onUsePassword }) => (
  <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={{ padding: 24, justifyContent: 'center' }}>
    <PinUnlockPanel onUsePassword={onUsePassword} />
  </ScreenContainer>
);
