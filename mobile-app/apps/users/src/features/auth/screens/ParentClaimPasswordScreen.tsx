import { useAuth, useCompleteParentClaim, type ClaimChannel } from '@erp/core';
import { Button, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useState } from 'react';
import { Pressable } from 'react-native';
import { ClaimField, ClaimScreenShell } from './claimUi';

interface Props {
  claimToken: string;
  channel: ClaimChannel;
  identifier: string;
  onBack: () => void;
}

/**
 * Parent claim — step 4: create a display name + password. On success the backend
 * returns a Sanctum token which establishes the session (RootGate takes over).
 */
export const ParentClaimPasswordScreen: React.FC<Props> = ({ claimToken, channel, identifier, onBack }) => {
  const { spacing } = useTheme();
  const { completeParentClaim } = useAuth();
  const complete = useCompleteParentClaim();

  const [name, setName] = useState('');
  const [email, setEmail] = useState(channel === 'email' ? identifier : '');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);

  const busy = complete.isPending;
  const error = localError ?? (complete.error as Error | null)?.message ?? null;
  const canSubmit = name.trim().length > 1 && password.length >= 8 && !busy;

  const handleSubmit = async () => {
    setLocalError(null);
    if (password !== confirm) {
      setLocalError('Passwords do not match.');
      return;
    }
    if (password.length < 8) {
      setLocalError('Password must be at least 8 characters.');
      return;
    }
    try {
      const data = await complete.mutateAsync({
        claimToken,
        name,
        password,
        passwordConfirmation: confirm,
        email: channel === 'phone' ? email || undefined : undefined,
      });
      await completeParentClaim({ token: data.token, user: data.user, expires_at: data.expires_at });
    } catch {
      /* surfaced via error banner */
    }
  };

  return (
    <ClaimScreenShell
      step={3}
      totalSteps={4}
      title="Create your account"
      subtitle="Set a display name and password to finish."
      onBack={onBack}
      error={error}
    >
      <ClaimField
        label="Your name"
        value={name}
        onChangeText={setName}
        placeholder="e.g. Jane Doe"
        icon="person-outline"
        editable={!busy}
      />

      {channel === 'phone' ? (
        <ClaimField
          label="Email (optional)"
          value={email}
          onChangeText={setEmail}
          placeholder="you@example.com"
          icon="mail-outline"
          autoCapitalize="none"
          keyboardType="email-address"
          editable={!busy}
        />
      ) : null}

      <ClaimField
        label="Password"
        value={password}
        onChangeText={setPassword}
        placeholder="At least 8 characters"
        icon="lock-closed-outline"
        secureTextEntry={!showPassword}
        autoCapitalize="none"
        editable={!busy}
        right={
          <Pressable onPress={() => setShowPassword((v) => !v)} hitSlop={8}>
            <Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color="rgba(255,255,255,0.45)" />
          </Pressable>
        }
      />

      <ClaimField
        label="Confirm password"
        value={confirm}
        onChangeText={setConfirm}
        placeholder="Re-enter password"
        icon="lock-closed-outline"
        secureTextEntry={!showPassword}
        autoCapitalize="none"
        editable={!busy}
        onSubmitEditing={handleSubmit}
      />

      <Button
        label="Create account & sign in"
        onPress={handleSubmit}
        loading={busy}
        disabled={!canSubmit}
        style={{ marginTop: spacing.sm }}
      />
    </ClaimScreenShell>
  );
};
