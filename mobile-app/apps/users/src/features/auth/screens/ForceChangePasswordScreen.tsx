import { accountApi, isStrongPassword, useAuth } from '@erp/core';
import { Button, PasswordField, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Text, View } from 'react-native';

/**
 * Shown only after a fresh sign-in when admin set must_change_password.
 * Does not block restored sessions / biometric unlock mid-day.
 */
export const ForceChangePasswordScreen: React.FC = () => {
  const { user, refreshUser, logout, clearForcePasswordChange } = useAuth();
  const { colors, palette, spacing, typography } = useTheme();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    if (!isStrongPassword(newPassword)) {
      setError('Password is missing a requirement listed below.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setError('New passwords do not match.');
      return;
    }
    setBusy(true);
    try {
      const res = await accountApi.changePassword({
        current_password: currentPassword.trim() || undefined,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });
      if (!res.success) throw new Error(res.message || 'Password change failed.');
      await refreshUser();
      clearForcePasswordChange();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not change password.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.lg, paddingTop: spacing.xl }}>
      <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '800' }}>
        Change your password
      </Text>
      <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, marginBottom: spacing.lg }}>
        {user?.name ? `Hi ${user.name.split(/\s+/)[0]}, ` : ''}
        your school requires a new password before you continue.
      </Text>

      {error ? <Text style={{ color: colors.error, marginBottom: spacing.sm }}>{error}</Text> : null}

      <PasswordField
        showCurrent
        currentValue={currentPassword}
        onCurrentChange={setCurrentPassword}
        currentLabel="Current password (optional if you were given a temporary one)"
        value={newPassword}
        onChangeText={setNewPassword}
        confirmValue={confirmPassword}
        onConfirmChange={setConfirmPassword}
        showConfirm
        username={user?.email ?? user?.phone ?? undefined}
      />

      <Button label={busy ? 'Saving…' : 'Save new password'} loading={busy} onPress={() => void submit()} />
      <View style={{ marginTop: spacing.md }}>
        <Button label="Sign out" variant="ghost" onPress={() => void logout()} />
      </View>
    </ScreenContainer>
  );
};
