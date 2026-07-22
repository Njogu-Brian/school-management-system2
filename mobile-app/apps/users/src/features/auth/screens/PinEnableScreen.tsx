import { useAuth, PIN_MIN_LENGTH, PIN_MAX_LENGTH } from '@erp/core';
import { AccentIcon, Button, ScreenContainer, useTheme } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', '⌫'] as const;

/**
 * Create / confirm a 4–6 digit app PIN after first login.
 */
export const PinEnableScreen: React.FC = () => {
  const { enablePin, skipPinEnrollment } = useAuth();
  const { palette, spacing, typography, colors, radius } = useTheme();
  const [step, setStep] = useState<'create' | 'confirm'>('create');
  const [pin, setPin] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);

  const active = step === 'create' ? pin : confirm;
  const setActive = step === 'create' ? setPin : setConfirm;

  const onKey = (key: string) => {
    if (key === '') return;
    if (key === '⌫') {
      setActive((v) => v.slice(0, -1));
      return;
    }
    setActive((v) => (v.length >= PIN_MAX_LENGTH ? v : v + key));
  };

  const canContinue =
    step === 'create'
      ? pin.length >= PIN_MIN_LENGTH
      : confirm.length >= PIN_MIN_LENGTH;

  const submit = async () => {
    if (step === 'create') {
      setStep('confirm');
      return;
    }
    if (pin !== confirm) {
      showError('PIN mismatch', 'The PINs do not match. Try again.');
      setConfirm('');
      setStep('create');
      setPin('');
      return;
    }
    setLoading(true);
    try {
      await enablePin(pin);
      showSuccess('PIN saved', 'You can unlock with your PIN next time.');
    } catch (err) {
      showError('PIN', err instanceof Error ? err.message : 'Could not save PIN.');
    } finally {
      setLoading(false);
    }
  };

  const dots = useMemo(
    () =>
      Array.from({ length: Math.max(active.length, PIN_MIN_LENGTH) }, (_, i) => i < active.length),
    [active.length],
  );

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={[styles.content, { padding: spacing.lg }]}>
      <AccentIcon name="keypad-outline" tone="indigo" size={72} />
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.headline.fontSize,
          fontWeight: '700',
          marginTop: spacing.lg,
          textAlign: 'center',
        }}
      >
        {step === 'create' ? 'Create a PIN' : 'Confirm your PIN'}
      </Text>
      <Text style={{ color: palette.textSecondary, textAlign: 'center', marginTop: spacing.sm }}>
        Use {PIN_MIN_LENGTH}–{PIN_MAX_LENGTH} digits. Your username is remembered for faster unlock.
      </Text>

      <View style={{ flexDirection: 'row', gap: 10, marginVertical: spacing.xl }}>
        {dots.map((filled, i) => (
          <View
            key={i}
            style={{
              width: 12,
              height: 12,
              borderRadius: 6,
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
            disabled={!key}
            style={[
              styles.key,
              {
                backgroundColor: key ? palette.surface : 'transparent',
                borderColor: key ? palette.border : 'transparent',
                borderRadius: radius.lg,
              },
            ]}
          >
            <Text style={{ color: palette.textPrimary, fontSize: 22, fontWeight: '600' }}>{key}</Text>
          </Pressable>
        ))}
      </View>

      <Button
        label={step === 'create' ? 'Continue' : 'Save PIN'}
        onPress={() => void submit()}
        disabled={!canContinue}
        loading={loading}
        style={{ alignSelf: 'stretch', marginTop: spacing.lg }}
      />
      <Button label="Not now" variant="ghost" onPress={skipPinEnrollment} disabled={loading} style={{ marginTop: spacing.sm }} />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: { alignItems: 'center', justifyContent: 'center' },
  pad: { width: '100%', maxWidth: 320, flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'center', gap: 10 },
  key: {
    width: '30%',
    aspectRatio: 1.4,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
