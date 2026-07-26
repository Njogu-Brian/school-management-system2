import { useAuth, useCompleteParentClaim, type ClaimChannel } from '@erp/core';
import { Button, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { ClaimField, ClaimScreenShell } from './claimUi';

interface Props {
  claimToken: string;
  channel: ClaimChannel;
  identifier: string;
  suggestedName?: string;
  suggestedEmail?: string;
  matchedRole?: string | null;
  onBack: () => void;
}

/**
 * Parent claim — step 4: review prefilled profile details + set a password.
 * Name/email come from the matched parent_info slot when available; parents can
 * keep or edit them before finishing.
 */
export const ParentClaimPasswordScreen: React.FC<Props> = ({
  claimToken,
  channel,
  identifier,
  suggestedName = '',
  suggestedEmail = '',
  matchedRole = null,
  onBack,
}) => {
  const { spacing, typography, radius } = useTheme();
  const { completeParentClaim } = useAuth();
  const complete = useCompleteParentClaim();

  const initialEmail = useMemo(() => {
    if (channel === 'email') return identifier;
    return suggestedEmail || '';
  }, [channel, identifier, suggestedEmail]);

  const [name, setName] = useState(suggestedName.trim());
  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);

  const busy = complete.isPending;
  const error = localError ?? (complete.error as Error | null)?.message ?? null;
  const canSubmit = name.trim().length > 1 && password.length >= 8 && !busy;
  const roleLabel =
    matchedRole === 'father' ? 'Father' : matchedRole === 'mother' ? 'Mother' : matchedRole === 'guardian' ? 'Guardian' : null;

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
        name: name.trim(),
        password,
        passwordConfirmation: confirm,
        email: channel === 'phone' ? email.trim() || undefined : undefined,
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
      title="Review your details"
      subtitle="Confirm or update your name and contact, then set a password."
      onBack={onBack}
      error={error}
    >
      {roleLabel || channel === 'phone' ? (
        <View
          style={{
            backgroundColor: 'rgba(255,255,255,0.06)',
            borderColor: 'rgba(255,255,255,0.14)',
            borderWidth: 1,
            borderRadius: radius.md,
            padding: spacing.md,
            marginBottom: spacing.md,
            gap: spacing.xs,
          }}
        >
          {roleLabel ? (
            <Text style={{ color: 'rgba(255,255,255,0.7)', fontSize: typography.caption.fontSize }}>
              Matched as {roleLabel} on school records
            </Text>
          ) : null}
          {channel === 'phone' ? (
            <Text style={{ color: '#fff', fontWeight: '600' }}>Verified phone: {identifier}</Text>
          ) : (
            <Text style={{ color: '#fff', fontWeight: '600' }}>Verified email: {identifier}</Text>
          )}
        </View>
      ) : null}

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
