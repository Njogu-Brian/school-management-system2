import { accountApi, isStrongPassword, passwordChecklist, useAuth } from '@erp/core';
import { Button, PasswordField, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Text, View } from 'react-native';

/**
 * Shown only after a fresh sign-in when admin set must_change_password.
 * Does not block restored sessions / biometric unlock mid-day.
 */
export const ForceChangePasswordScreen: React.FC = () => {
  const { user, refreshUser, logout, clearForcePasswordChange, recordPasswordForUnlock } = useAuth();
  const { colors, palette, spacing, typography } = useTheme();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [currentError, setCurrentError] = useState<string | null>(null);
  const [newError, setNewError] = useState<string | null>(null);
  const [confirmError, setConfirmError] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    setCurrentError(null);
    setNewError(null);
    setConfirmError(null);

    if (!currentPassword.trim()) {
      setCurrentError('Enter the password you just used to sign in.');
      return;
    }
    if (!isStrongPassword(newPassword)) {
      const missing = passwordChecklist(newPassword)
        .filter((c) => !c.ok)
        .map((c) => c.label)
        .join(', ');
      setNewError(missing ? `Still needed: ${missing}` : 'Password is missing a requirement listed below.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setConfirmError('The two new passwords do not match.');
      return;
    }
    setBusy(true);
    try {
      const res = await accountApi.changePassword({
        current_password: currentPassword.trim(),
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });
      if (!res.success) throw new Error(res.message || 'Password change failed.');
      recordPasswordForUnlock(newPassword);
      await refreshUser();
      clearForcePasswordChange();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Could not change password.';
      if (/current password/i.test(message)) {
        setCurrentError('That current password is incorrect.');
      } else if (/match/i.test(message)) {
        setConfirmError('The two new passwords do not match.');
      } else {
        setError(message);
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenContainer
      scroll
      clearFloatingTabBar={false}
      contentContainerStyle={{ padding: spacing.lg, paddingTop: spacing.xl }}
    >
      <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '800' }}>
        Change your password
      </Text>
      <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, marginBottom: spacing.lg }}>
        {user?.name ? `Hi ${user.name.split(/\s+/)[0]}, ` : ''}
        you signed in with a temporary password. Choose a new one before you continue. Use “Suggest a strong password”
        or type your own.
      </Text>

      {error ? <Text style={{ color: colors.error, marginBottom: spacing.sm }}>{error}</Text> : null}

      <PasswordField
        showCurrent
        currentValue={currentPassword}
        onCurrentChange={setCurrentPassword}
        currentLabel="Current password"
        currentError={currentError}
        value={newPassword}
        onChangeText={setNewPassword}
        confirmValue={confirmPassword}
        onConfirmChange={setConfirmPassword}
        showConfirm
        disableAutofill
        error={newError}
        confirmError={confirmError}
        username={user?.email ?? user?.phone ?? undefined}
      />

      <Button label={busy ? 'Saving…' : 'Save new password'} loading={busy} onPress={() => void submit()} />
      <View style={{ marginTop: spacing.md }}>
        <Button label="Sign out" variant="ghost" onPress={() => void logout()} />
      </View>
    </ScreenContainer>
  );
};
