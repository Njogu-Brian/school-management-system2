import { accountApi, useAuth } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Text, TextInput, View } from 'react-native';

/**
 * Blocking screen when admin/server set must_change_password.
 * Current password is optional when the flag is set (temp passwords / forced reset).
 */
export const ForceChangePasswordScreen: React.FC = () => {
  const { user, refreshUser, logout } = useAuth();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    if (!newPassword || newPassword !== confirmPassword) {
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
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not change password.');
    } finally {
      setBusy(false);
    }
  };

  const fieldStyle = {
    borderWidth: 1,
    borderColor: palette.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    color: palette.textPrimary,
    marginBottom: spacing.sm,
    backgroundColor: palette.surface,
  } as const;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.lg, paddingTop: spacing.xl }}>
      <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '800' }}>
        Change your password
      </Text>
      <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, marginBottom: spacing.lg }}>
        {user?.name ? `Hi ${user.name.split(/\s+/)[0]}, ` : ''}
        your school requires a new password before you continue.
      </Text>

      {error ? (
        <Text style={{ color: colors.error, marginBottom: spacing.sm }}>{error}</Text>
      ) : null}

      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
        Current password (optional if you were given a temporary one)
      </Text>
      <TextInput
        value={currentPassword}
        onChangeText={setCurrentPassword}
        secureTextEntry
        autoCapitalize="none"
        placeholder="Current password"
        placeholderTextColor={palette.textMuted}
        style={fieldStyle}
      />
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
        New password
      </Text>
      <TextInput
        value={newPassword}
        onChangeText={setNewPassword}
        secureTextEntry
        autoCapitalize="none"
        placeholder="New password"
        placeholderTextColor={palette.textMuted}
        style={fieldStyle}
      />
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
        Confirm new password
      </Text>
      <TextInput
        value={confirmPassword}
        onChangeText={setConfirmPassword}
        secureTextEntry
        autoCapitalize="none"
        placeholder="Confirm new password"
        placeholderTextColor={palette.textMuted}
        style={fieldStyle}
      />

      <Button label={busy ? 'Saving…' : 'Save new password'} loading={busy} onPress={() => void submit()} />
      <View style={{ marginTop: spacing.md }}>
        <Button label="Sign out" variant="ghost" onPress={() => void logout()} />
      </View>
    </ScreenContainer>
  );
};
