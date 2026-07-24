import {
  clearPinEnrollment,
  getRememberedUsername,
  isPinEnabled,
  PIN_MAX_LENGTH,
  PIN_MIN_LENGTH,
  useAuth,
} from '@erp/core';
import { AcademicScreenHeader, Button, ConfirmDialog, PinKeypad, ScreenContainer, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import Constants from 'expo-constants';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSurfaceModeControl } from '../../../providers/AppThemeProvider';
import { showError, showSuccess } from '../../shared/utils/feedback';

/** Live on ERP (public, no login) — use for Play Store privacy URL */
const LEGAL_BASE = 'https://erp.royalkingsschools.sc.ke';

export const SettingsScreen: React.FC = () => {
  const navigation = useNavigation();
  const { logout, enablePin } = useAuth();
  const { palette, spacing, typography, radius, themeMode, setThemeMode, colors } = useTheme();
  const { surfaceMode, setSurfaceMode } = useSurfaceModeControl();

  const [pinOn, setPinOn] = useState(false);
  const [rememberedUser, setRememberedUser] = useState<string | null>(null);
  const [pinSetupOpen, setPinSetupOpen] = useState(false);
  const [pinStep, setPinStep] = useState<'create' | 'confirm'>('create');
  const [pinDraft, setPinDraft] = useState('');
  const [pinConfirm, setPinConfirm] = useState('');
  const [pinLoading, setPinLoading] = useState(false);
  const [disableConfirmOpen, setDisableConfirmOpen] = useState(false);

  const refreshSecurity = useCallback(async () => {
    setPinOn(await isPinEnabled());
    setRememberedUser(await getRememberedUsername());
  }, []);

  useEffect(() => {
    void refreshSecurity();
  }, [refreshSecurity]);

  const activePin = pinStep === 'create' ? pinDraft : pinConfirm;
  const setActivePin = pinStep === 'create' ? setPinDraft : setPinConfirm;

  const onPinKey = (key: string) => {
    if (key === '⌫') {
      setActivePin((v) => v.slice(0, -1));
      return;
    }
    setActivePin((v) => (v.length >= PIN_MAX_LENGTH ? v : v + key));
  };

  const closePinSetup = () => {
    setPinSetupOpen(false);
    setPinStep('create');
    setPinDraft('');
    setPinConfirm('');
  };

  const savePin = async () => {
    if (pinStep === 'create') {
      if (pinDraft.length < PIN_MIN_LENGTH) return;
      setPinStep('confirm');
      return;
    }
    if (pinDraft !== pinConfirm) {
      showError('PIN mismatch', 'The PINs do not match. Try again.');
      setPinDraft('');
      setPinConfirm('');
      setPinStep('create');
      return;
    }
    setPinLoading(true);
    try {
      await enablePin(pinConfirm);
      showSuccess('PIN saved', 'You can unlock with your PIN next time you open the app.');
      closePinSetup();
      await refreshSecurity();
    } catch (err) {
      showError('PIN', err instanceof Error ? err.message : 'Could not save PIN.');
    } finally {
      setPinLoading(false);
    }
  };

  const disablePin = async () => {
    await clearPinEnrollment();
    setDisableConfirmOpen(false);
    showSuccess('PIN removed', 'Sign in with your password next time.');
    await refreshSecurity();
  };

  const pinDots = useMemo(
    () =>
      Array.from({ length: Math.max(activePin.length, PIN_MIN_LENGTH) }, (_, i) => i < activePin.length),
    [activePin.length],
  );

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Settings"
        onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
      />

      <View style={[styles.section, { backgroundColor: palette.surface, borderColor: palette.border, borderRadius: radius.lg, padding: spacing.md, marginBottom: spacing.md }]}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Security</Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
          App PIN unlocks without retyping your password. Your sign-in username is saved on this device for faster unlock.
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.xs }}>
          PIN status:{' '}
          <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{pinOn ? 'Enabled' : 'Off'}</Text>
        </Text>
        {rememberedUser ? (
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
            Remembered username:{' '}
            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{rememberedUser}</Text>
          </Text>
        ) : (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
            No username saved yet — sign in once with password to remember it.
          </Text>
        )}
        {pinSetupOpen ? (
          <View style={{ marginBottom: spacing.md }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', marginBottom: spacing.sm }}>
              {pinStep === 'create' ? 'Enter a new PIN' : 'Confirm your PIN'}
            </Text>
            <View style={{ flexDirection: 'row', justifyContent: 'center', gap: 8, marginBottom: spacing.md }}>
              {pinDots.map((filled, i) => (
                <View
                  key={i}
                  style={{
                    width: 10,
                    height: 10,
                    borderRadius: 5,
                    backgroundColor: filled ? colors.primary : palette.border,
                  }}
                />
              ))}
            </View>
            <PinKeypad onKey={onPinKey} disabled={pinLoading} />
            <Button
              label={pinStep === 'create' ? 'Continue' : 'Save PIN'}
              onPress={() => void savePin()}
              loading={pinLoading}
              disabled={activePin.length < PIN_MIN_LENGTH}
              style={{ marginTop: spacing.sm }}
            />
            <Button label="Cancel" variant="ghost" onPress={closePinSetup} disabled={pinLoading} />
          </View>
        ) : (
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            <Button
              label={pinOn ? 'Change PIN' : 'Enable PIN'}
              variant="secondary"
              onPress={() => setPinSetupOpen(true)}
            />
            {pinOn ? (
              <Button label="Disable PIN" variant="ghost" onPress={() => setDisableConfirmOpen(true)} />
            ) : null}
          </View>
        )}
      </View>

      <View
        style={{
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          borderWidth: 1,
          borderColor: palette.border,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Appearance</Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
          Theme: {themeMode}
        </Text>
        <View style={{ flexDirection: 'row', gap: spacing.sm, flexWrap: 'wrap' }}>
          {(['auto', 'light', 'dark'] as const).map((mode) => (
            <Button
              key={mode}
              label={mode}
              variant={themeMode === mode ? 'primary' : 'secondary'}
              onPress={() => setThemeMode(mode)}
            />
          ))}
        </View>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginTop: spacing.md,
            marginBottom: spacing.sm,
          }}
        >
          Surface: {surfaceMode}
        </Text>
        <View style={{ flexDirection: 'row', gap: spacing.sm }}>
          <Button
            label="Default"
            variant={surfaceMode === 'default' ? 'primary' : 'secondary'}
            onPress={() => setSurfaceMode('default')}
          />
          <Button
            label="AMOLED"
            variant={surfaceMode === 'amoled' ? 'primary' : 'secondary'}
            onPress={() => setSurfaceMode('amoled')}
          />
        </View>
      </View>

      <View
        style={{
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          borderWidth: 1,
          borderColor: palette.border,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
          About & legal
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
          Royal Kings Users · v{Constants.expoConfig?.version ?? '1.0.0'}
        </Text>
        {(
          [
            { label: 'Privacy policy', url: `${LEGAL_BASE}/privacy` },
            { label: 'Terms of use', url: `${LEGAL_BASE}/terms` },
          ] as const
        ).map((row) => (
          <Pressable
            key={row.url}
            onPress={() => void Linking.openURL(row.url)}
            accessibilityRole="link"
            style={({ pressed }) => ({
              paddingVertical: spacing.sm,
              opacity: pressed ? 0.7 : 1,
            })}
          >
            <Text style={{ color: colors.primary, fontWeight: '600' }}>{row.label}</Text>
          </Pressable>
        ))}
      </View>

      <Button label="Sign out" variant="ghost" onPress={logout} />

      <ConfirmDialog
        visible={disableConfirmOpen}
        title="Disable PIN?"
        message="You will need your password to sign in. Your remembered username stays on this device."
        confirmLabel="Disable PIN"
        cancelLabel="Cancel"
        destructive
        onConfirm={() => void disablePin()}
        onCancel={() => setDisableConfirmOpen(false)}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  section: { borderWidth: 1 },
});
