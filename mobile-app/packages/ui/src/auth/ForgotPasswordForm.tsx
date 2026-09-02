import { Button, ScreenContainer, TextField, PasswordField, isStrongPassword, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Pressable, Text, View } from 'react-native';

type Step = 'identify' | 'code' | 'password';

type Props = {
  onBack: () => void;
  requestOtp: (identifier: string) => Promise<void>;
  verifyOtp: (identifier: string, code: string) => Promise<string>;
  resetPassword: (payload: {
    identifier: string;
    token: string;
    password: string;
    password_confirmation: string;
  }) => Promise<void>;
};

export const ForgotPasswordForm: React.FC<Props> = ({ onBack, requestOtp, verifyOtp, resetPassword }) => {
  const { colors, palette, spacing, typography } = useTheme();
  const [step, setStep] = useState<Step>('identify');
  const [identifier, setIdentifier] = useState('');
  const [code, setCode] = useState('');
  const [token, setToken] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);

  const run = async (fn: () => Promise<void>) => {
    setError(null);
    setBusy(true);
    try {
      await fn();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.lg, paddingTop: spacing.xl }}>
      <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '800' }}>
        Reset password
      </Text>
      <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, marginBottom: spacing.lg }}>
        We will send a 6-digit code to your phone or email so you can prove this account is yours.
      </Text>
      {error ? <Text style={{ color: colors.error, marginBottom: spacing.sm }}>{error}</Text> : null}
      {info ? <Text style={{ color: colors.success, marginBottom: spacing.sm }}>{info}</Text> : null}

      {step === 'identify' ? (
        <>
          <TextField
            label="Email or phone"
            value={identifier}
            onChangeText={setIdentifier}
            autoCapitalize="none"
            keyboardType="email-address"
            autoComplete="username"
          />
          <Button
            label={busy ? 'Sending…' : 'Send code'}
            loading={busy}
            onPress={() =>
              void run(async () => {
                await requestOtp(identifier.trim());
                setInfo('Code sent. Check your SMS or email.');
                setStep('code');
              })
            }
          />
        </>
      ) : null}

      {step === 'code' ? (
        <>
          <TextField
            label="6-digit code"
            value={code}
            onChangeText={(t) => setCode(t.replace(/\D/g, '').slice(0, 6))}
            keyboardType="number-pad"
          />
          <Button
            label={busy ? 'Checking…' : 'Verify code'}
            loading={busy}
            onPress={() =>
              void run(async () => {
                const nextToken = await verifyOtp(identifier.trim(), code);
                setToken(nextToken);
                setInfo('Code confirmed. Choose a new password.');
                setStep('password');
              })
            }
          />
        </>
      ) : null}

      {step === 'password' ? (
        <>
          <PasswordField
            value={password}
            onChangeText={setPassword}
            confirmValue={confirm}
            onConfirmChange={setConfirm}
            showConfirm
            username={identifier.trim()}
          />
          <Button
            label={busy ? 'Saving…' : 'Save new password'}
            loading={busy}
            onPress={() =>
              void run(async () => {
                if (!isStrongPassword(password)) {
                  throw new Error('Password is missing a requirement listed above.');
                }
                if (password !== confirm) {
                  throw new Error('New passwords do not match.');
                }
                await resetPassword({
                  identifier: identifier.trim(),
                  token,
                  password,
                  password_confirmation: confirm,
                });
              })
            }
          />
        </>
      ) : null}

      <View style={{ marginTop: spacing.md }}>
        <Pressable onPress={onBack}>
          <Text style={{ color: colors.primary, fontWeight: '700' }}>Back to sign in</Text>
        </Pressable>
      </View>
    </ScreenContainer>
  );
};
