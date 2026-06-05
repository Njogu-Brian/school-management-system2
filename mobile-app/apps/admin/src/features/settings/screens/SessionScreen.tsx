import {
  getSessionMeta,
  useActiveSessions,
  useAuth,
  useRevokeOtherSessions,
  useRevokeSession,
  type PersistedSessionMeta,
} from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import Constants from 'expo-constants';
import React, { useEffect, useState } from 'react';
import { Alert, Platform, Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showSuccess } from '../../shared/utils/feedback';

export interface SessionScreenProps {
  onBack?: () => void;
}

export const SessionScreen: React.FC<SessionScreenProps> = ({ onBack }) => {
  const { user, logout } = useAuth();
  const [meta, setMeta] = useState<PersistedSessionMeta | null>(null);
  const { palette, spacing, fontSizes, colors, isDark, toggleTheme, themeMode, setThemeMode } = useTheme();
  const sessionsQuery = useActiveSessions();
  const revokeSession = useRevokeSession();
  const revokeOthers = useRevokeOtherSessions();

  useEffect(() => {
    void getSessionMeta().then(setMeta);
  }, []);

  const deviceName = Constants.deviceName ?? 'This device';

  const forceReauth = () => {
    Alert.alert('Sign out', 'You will need to sign in again on this device.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Sign out', style: 'destructive', onPress: () => void logout() },
    ]);
  };

  const revokeDevice = (tokenId: number, label: string) => {
    confirmAction('Logout device', `End session on ${label}?`, 'Logout', async () => {
      await revokeSession.mutateAsync(tokenId);
      showSuccess('Done', 'Session ended.');
    }, true);
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      {onBack ? <AcademicScreenHeader title="Session & security" onBack={onBack} /> : null}
      <FinanceFieldSection
        title="Appearance"
        rows={[
          {
            label: 'Dark mode',
            value: isDark ? 'On' : 'Off',
          },
        ]}
      />
      <View style={[styles.deviceRow, { borderColor: palette.border, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }]}>
        <Text style={{ color: palette.textPrimary }}>Use dark theme</Text>
        <Switch value={isDark} onValueChange={() => toggleTheme()} />
      </View>
      <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm, marginBottom: spacing.md }}>
        {(['light', 'dark', 'auto'] as const).map((mode) => (
          <Pressable
            key={mode}
            onPress={() => setThemeMode(mode)}
            style={{
              paddingHorizontal: 12,
              paddingVertical: 8,
              borderRadius: 8,
              borderWidth: 1,
              borderColor: themeMode === mode ? colors.primary : palette.border,
              backgroundColor: themeMode === mode ? `${colors.primary}18` : 'transparent',
            }}
          >
            <Text style={{ color: themeMode === mode ? colors.primary : palette.textSecondary, fontWeight: '600', textTransform: 'capitalize' }}>
              {mode}
            </Text>
          </Pressable>
        ))}
      </View>

      <FinanceFieldSection
        title="Current session"
        rows={[
          { label: 'User', value: user?.name ?? user?.email ?? '—' },
          { label: 'Device', value: deviceName },
          { label: 'Platform', value: Platform.OS },
          {
            label: 'Signed in',
            value: meta?.startedAt ? formatDateTimeLabel(new Date(meta.startedAt).toISOString()) : '—',
          },
          {
            label: 'Last activity',
            value: meta?.lastActivityAt ? formatDateTimeLabel(new Date(meta.lastActivityAt).toISOString()) : '—',
          },
          { label: 'Remember me', value: meta?.rememberMe ? 'Yes' : 'No' },
        ]}
      />

      <Text style={{ fontWeight: '700', marginTop: spacing.lg, color: palette.textPrimary }}>Active devices</Text>
      {(sessionsQuery.data ?? []).map((session) => (
        <View key={session.id} style={[styles.deviceRow, { borderColor: palette.border }]}>
          <Text style={{ fontWeight: '600', color: palette.textPrimary }}>
            {session.device}{session.is_current ? ' (this device)' : ''}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
            Last active {formatDateTimeLabel(session.last_activity)}
          </Text>
          {!session.is_current ? (
            <Pressable onPress={() => revokeDevice(session.id, session.device)} style={{ marginTop: 6 }}>
              <Text style={{ color: colors.error, fontSize: fontSizes.xs, fontWeight: '600' }}>Logout device</Text>
            </Pressable>
          ) : null}
        </View>
      ))}

      <Button
        label="Logout all other devices"
        variant="ghost"
        onPress={() =>
          confirmAction('Logout others', 'End all sessions except this device?', 'Logout all', async () => {
            await revokeOthers.mutateAsync();
            showSuccess('Done', 'Other sessions ended.');
          }, true)
        }
        style={{ marginTop: spacing.md }}
      />
      <Button label="Force re-authentication" variant="ghost" onPress={forceReauth} style={{ marginTop: spacing.sm }} />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  deviceRow: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginTop: 8 },
});
