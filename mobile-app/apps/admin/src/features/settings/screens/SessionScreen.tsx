import {
  getRememberedUsername,
  getSessionMeta,
  isPinEnabled,
  PIN_MAX_LENGTH,
  PIN_MIN_LENGTH,
  useActiveSessions,
  useAuth,
  useNotificationPreferences,
  useRevokeOtherSessions,
  useRevokeSession,
  useUpdateNotificationPreferences,
  type NotificationPreferences,
  type PersistedSessionMeta,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ConfirmDialog,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import Constants from 'expo-constants';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Platform, Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { useSurfaceModeControl } from '../../../providers/AppThemeProvider';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

export interface SessionScreenProps {
  onBack?: () => void;
}

export const SessionScreen: React.FC<SessionScreenProps> = ({ onBack }) => {
  const { user, logout, enablePin, disablePin } = useAuth();
  const [meta, setMeta] = useState<PersistedSessionMeta | null>(null);
  const [signOutVisible, setSignOutVisible] = useState(false);
  const [pinOn, setPinOn] = useState(false);
  const [remembered, setRemembered] = useState<string | null>(null);
  const [pinDraft, setPinDraft] = useState('');
  const [pinBusy, setPinBusy] = useState(false);
  const { palette, spacing, typography, radius, elevation, colors, isDark, toggleTheme, themeMode, setThemeMode } =
    useTheme();
  const { surfaceMode, setSurfaceMode } = useSurfaceModeControl();
  const sessionsQuery = useActiveSessions();
  const revokeSession = useRevokeSession();
  const revokeOthers = useRevokeOtherSessions();
  const prefsQuery = useNotificationPreferences();
  const updatePrefs = useUpdateNotificationPreferences();

  const togglePref = (key: keyof NotificationPreferences) => {
    const current = prefsQuery.data;
    if (!current) return;
    void updatePrefs.mutateAsync({ ...current, [key]: !current[key] });
  };

  useEffect(() => {
    void getSessionMeta().then(setMeta);
    void isPinEnabled().then(setPinOn);
    void getRememberedUsername().then(setRemembered);
  }, []);

  const savePinFromDraft = async () => {
    if (!/^\d+$/.test(pinDraft) || pinDraft.length < PIN_MIN_LENGTH || pinDraft.length > PIN_MAX_LENGTH) {
      showError('Invalid PIN', `Use ${PIN_MIN_LENGTH}–${PIN_MAX_LENGTH} digits.`);
      return;
    }
    setPinBusy(true);
    try {
      await enablePin(pinDraft);
      setPinDraft('');
      setPinOn(true);
      setRemembered(await getRememberedUsername());
      showSuccess('PIN saved', 'You can unlock with your PIN next time.');
    } catch (err) {
      showError('PIN', err instanceof Error ? err.message : 'Could not save PIN.');
    } finally {
      setPinBusy(false);
    }
  };

  const deviceName = Constants.deviceName ?? 'This device';

  const forceReauth = () => {
    setSignOutVisible(true);
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
      <View
        style={[
          styles.deviceRow,
          elevation[1],
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            borderRadius: radius.card,
            padding: spacing.md,
            marginTop: spacing.sm,
            flexDirection: 'row',
            justifyContent: 'space-between',
            alignItems: 'center',
            minHeight: 48,
          },
        ]}
      >
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.body.fontSize,
            fontWeight: '600',
          }}
        >
          Use dark theme
        </Text>
        <Switch value={isDark} onValueChange={() => toggleTheme()} />
      </View>
      <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm, marginBottom: spacing.sm }}>
        {(['light', 'dark', 'auto'] as const).map((mode) => (
          <Pressable
            key={mode}
            onPress={() => setThemeMode(mode)}
            style={{
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.sm,
              minHeight: 48,
              justifyContent: 'center',
              borderRadius: radius.control,
              borderWidth: StyleSheet.hairlineWidth,
              borderColor: themeMode === mode ? palette.primary : palette.borderSubtle,
              backgroundColor: themeMode === mode ? palette.primaryMuted : palette.surfaceRaised,
            }}
          >
            <Text
              style={{
                color: themeMode === mode ? palette.primary : palette.textSub,
                fontWeight: '600',
                fontSize: typography.body.fontSize,
                textTransform: 'capitalize',
              }}
            >
              {mode}
            </Text>
          </Pressable>
        ))}
      </View>
      <View
        style={[
          styles.deviceRow,
          elevation[1],
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            borderRadius: radius.card,
            padding: spacing.md,
            marginBottom: spacing.md,
            flexDirection: 'row',
            justifyContent: 'space-between',
            alignItems: 'center',
            minHeight: 48,
            opacity: isDark ? 1 : 0.5,
          },
        ]}
      >
        <View style={{ flex: 1, marginRight: spacing.sm }}>
          <Text
            style={{
              color: palette.textMain,
              fontSize: typography.body.fontSize,
              fontWeight: '600',
            }}
          >
            AMOLED black
          </Text>
          <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
            True black canvas in dark mode (saves battery on OLED)
          </Text>
        </View>
        <Switch
          value={surfaceMode === 'amoled'}
          disabled={!isDark}
          onValueChange={(on) => setSurfaceMode(on ? 'amoled' : 'default')}
        />
      </View>

      <Text
        style={{
          fontWeight: '700',
          marginTop: spacing.lg,
          marginBottom: spacing.sm,
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
        }}
      >
        App PIN
      </Text>
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
        Status: {pinOn ? 'On' : 'Off'}
        {remembered ? ` · Remembered user: ${remembered}` : ''}
      </Text>
      <TextField
        label={`${pinOn ? 'Change' : 'Create'} PIN (${PIN_MIN_LENGTH}–${PIN_MAX_LENGTH} digits)`}
        value={pinDraft}
        onChangeText={(t) => setPinDraft(t.replace(/\D/g, '').slice(0, PIN_MAX_LENGTH))}
        keyboardType="number-pad"
        secureTextEntry
        placeholder="••••"
      />
      <Button
        label={pinOn ? 'Update PIN' : 'Save PIN'}
        onPress={() => void savePinFromDraft()}
        loading={pinBusy}
        style={{ marginTop: spacing.sm }}
      />
      {pinOn ? (
        <Button
          label="Remove PIN"
          variant="ghost"
          onPress={() =>
            confirmAction('Remove PIN', 'You will need your password next time.', 'Remove', async () => {
              await disablePin();
              setPinOn(false);
              showSuccess('PIN removed', 'PIN unlock is off.');
            }, true)
          }
          style={{ marginTop: spacing.xs }}
        />
      ) : null}

      <Text
        style={{
          fontWeight: '700',
          marginTop: spacing.lg,
          marginBottom: spacing.sm,
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
        }}
      >
        Notifications
      </Text>
      {(
        [
          ['push_enabled', 'Push notifications'],
          ['email_enabled', 'Email notifications'],
          ['sms_enabled', 'SMS notifications'],
          ['attendance_alerts', 'Attendance alerts'],
          ['fee_reminders', 'Fee reminders'],
          ['announcements', 'Announcements'],
        ] as const
      ).map(([key, label]) => (
        <View
          key={key}
          style={[
            styles.deviceRow,
            elevation[1],
            {
              backgroundColor: palette.surfaceRaised,
              borderColor: palette.borderSubtle,
              borderRadius: radius.card,
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.sm,
              marginTop: spacing.sm,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'space-between',
              minHeight: 48,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textPrimary,
              flex: 1,
              fontSize: typography.body.fontSize,
              paddingRight: spacing.sm,
            }}
          >
            {label}
          </Text>
          <Switch
            value={prefsQuery.data?.[key] ?? false}
            onValueChange={() => togglePref(key)}
            disabled={!prefsQuery.data || updatePrefs.isPending}
          />
        </View>
      ))}

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
            value: meta?.lastActivityAt
              ? formatDateTimeLabel(new Date(meta.lastActivityAt).toISOString())
              : '—',
          },
          { label: 'Remember me', value: meta?.rememberMe ? 'Yes' : 'No' },
        ]}
      />

      <Text
        style={{
          fontWeight: '700',
          marginTop: spacing.lg,
          marginBottom: spacing.sm,
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
        }}
      >
        Active devices
      </Text>
      {sessionsQuery.isLoading ? (
        <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      ) : sessionsQuery.isError ? (
        <EmptyState
          title="Could not load sessions"
          message={(sessionsQuery.error as Error)?.message ?? 'Try again in a moment.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void sessionsQuery.refetch()}
        />
      ) : (sessionsQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No active devices"
          message="Only this session appears to be signed in."
          icon="phone-portrait-outline"
        />
      ) : (
        (sessionsQuery.data ?? []).map((session) => (
          <View
            key={session.id}
            style={[
              styles.deviceRow,
              elevation[1],
              {
                backgroundColor: palette.surfaceRaised,
                borderColor: palette.borderSubtle,
                borderRadius: radius.card,
                padding: spacing.md,
                marginTop: spacing.sm,
              },
            ]}
          >
            <Text
              style={{
                fontWeight: '600',
                color: palette.textPrimary,
                fontSize: typography.body.fontSize,
              }}
            >
              {session.device}
              {session.is_current ? ' (this device)' : ''}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs,
              }}
            >
              Last active {formatDateTimeLabel(session.last_activity)}
            </Text>
            {!session.is_current ? (
              <Pressable
                onPress={() => revokeDevice(session.id, session.device)}
                style={{ marginTop: spacing.sm, minHeight: 48, justifyContent: 'center' }}
              >
                <Text
                  style={{
                    color: colors.error,
                    fontSize: typography.body.fontSize,
                    fontWeight: '600',
                  }}
                >
                  Logout device
                </Text>
              </Pressable>
            ) : null}
          </View>
        ))
      )}

      <Button
        label="Logout all other devices"
        variant="ghost"
        onPress={() =>
          confirmAction(
            'Logout others',
            'End all sessions except this device?',
            'Logout all',
            async () => {
              await revokeOthers.mutateAsync();
              showSuccess('Done', 'Other sessions ended.');
            },
            true,
          )
        }
        style={{ marginTop: spacing.md }}
      />
      <Button
        label="Force re-authentication"
        variant="ghost"
        onPress={forceReauth}
        style={{ marginTop: spacing.sm }}
      />

      <ConfirmDialog
        visible={signOutVisible}
        title="Sign out"
        message="You will need to sign in again on this device."
        confirmLabel="Sign out"
        cancelLabel="Cancel"
        destructive
        onConfirm={() => {
          setSignOutVisible(false);
          void logout();
        }}
        onCancel={() => setSignOutVisible(false)}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  deviceRow: { borderWidth: StyleSheet.hairlineWidth },
});
