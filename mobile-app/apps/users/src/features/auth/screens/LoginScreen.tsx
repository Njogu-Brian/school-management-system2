import { getRememberedUsername, API_BASE_URL, useAuth, useBiometricAuth, useBranding } from '@erp/core';
import { Button, ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  ImageBackground,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  type TextInputProps,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { PinUnlockPanel } from './PinUnlockPanel';

type AuthMode = 'password' | 'otp';

type LoginAnnouncement = { id: number; title: string; content: string };

/**
 * Flagship login — full-bleed hero, dark glass sheet, biometric-first when enrolled.
 */
export const LoginScreen: React.FC = () => {
  const {
    login,
    requestLoginOtp,
    verifyLoginOtp,
    submitting,
    error,
  } = useAuth();
  const {
    unlockAvailable,
    isLocked,
    typeLabel,
    unlock,
    refresh: refreshBiometric,
    submitting: biometricSubmitting,
  } = useBiometricAuth();
  const { colors, spacing, typography, radius } = useTheme();
  const insets = useSafeAreaInsets();
  const { schoolName, logoUrl, loginBackgroundUrl, loading: brandingLoading, branding, colorOverrides } =
    useBranding();

  const [logoFailed, setLogoFailed] = useState(false);
  const [bgFailed, setBgFailed] = useState(false);
  const [mode, setMode] = useState<AuthMode>('password');
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [remember, setRemember] = useState(true);
  const [showPassword, setShowPassword] = useState(false);
  const [showCredentialForm, setShowCredentialForm] = useState(false);
  const [preferPassword, setPreferPassword] = useState(false);
  const [announcements, setAnnouncements] = useState<LoginAnnouncement[]>([]);

  useEffect(() => {
    void refreshBiometric();
  }, [refreshBiometric]);

  useEffect(() => {
    void getRememberedUsername().then((name) => {
      if (name) setIdentifier(name);
    });
  }, []);

  useEffect(() => {
    if (unlockAvailable && !isLocked) {
      setShowCredentialForm(false);
    }
  }, [unlockAvailable, isLocked]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const base = API_BASE_URL.replace(/\/$/, '');
        const res = await fetch(`${base}/public/announcements?limit=5`);
        const json = (await res.json()) as {
          success?: boolean;
          data?: LoginAnnouncement[];
        };
        if (!cancelled && json.success && Array.isArray(json.data)) {
          setAnnouncements(json.data);
        }
      } catch {
        /* ignore — login still works without announcements */
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const busy = submitting || biometricSubmitting;
  const canPasswordSubmit = identifier.trim().length > 0 && password.length > 0 && !busy;
  const canRequestOtp = identifier.trim().length > 0 && !busy;
  const canVerifyOtp = otpSent && otpCode.trim().length === 6 && !busy;
  const showBackground = Boolean(loginBackgroundUrl) && !bgFailed;
  const bioFirst = unlockAvailable && !isLocked && !showCredentialForm && !preferPassword;

  const gradientStops = useMemo((): [string, string, string] => {
    const primary = branding?.colors?.primary ?? colorOverrides.primary ?? colors.primary;
    return [primary, '#003366', '#0c1018'];
  }, [branding?.colors, colorOverrides.primary, colors.primary]);

  const handlePasswordSubmit = async (): Promise<void> => {
    if (!canPasswordSubmit) return;
    try {
      await login({ identifier: identifier.trim(), password, remember });
    } catch {
      /* auth error state */
    }
  };

  const handleRequestOtp = async (): Promise<void> => {
    if (!canRequestOtp) return;
    try {
      await requestLoginOtp(identifier.trim());
      setOtpSent(true);
      showSuccess('OTP sent', 'Enter the 6-digit code sent to your registered phone.');
    } catch {
      /* auth error state */
    }
  };

  const handleVerifyOtp = async (): Promise<void> => {
    if (!canVerifyOtp) return;
    try {
      await verifyLoginOtp(identifier.trim(), otpCode.trim());
    } catch {
      /* auth error state */
    }
  };

  const handleBiometricUnlock = async (): Promise<void> => {
    if (isLocked) {
      showError(
        'Biometric sign-in locked',
        'Sign in with your email and password. You can use biometrics again after a successful sign-in.',
      );
      setShowCredentialForm(true);
      return;
    }
    try {
      await unlock();
    } catch (err) {
      showError('Unlock failed', err instanceof Error ? err.message : 'Biometric unlock failed.');
      await refreshBiometric();
      setShowCredentialForm(true);
    }
  };

  const modeTabs = (
    <View
      style={[
        styles.modeRow,
        {
          backgroundColor: 'rgba(255,255,255,0.06)',
          borderRadius: radius.control,
          marginBottom: spacing.md,
          padding: 4,
        },
      ]}
    >
      {(['password', 'otp'] as const).map((m) => {
        const active = mode === m;
        return (
          <Pressable
            key={m}
            onPress={() => {
              setMode(m);
              setOtpSent(false);
              setOtpCode('');
            }}
            style={[
              styles.modeChip,
              {
                backgroundColor: active ? colors.primary : 'transparent',
                borderRadius: radius.md,
              },
            ]}
          >
            <Text
              style={{
                color: active ? '#fff' : 'rgba(255,255,255,0.65)',
                fontWeight: '700',
                fontSize: typography.caption.fontSize,
              }}
            >
              {m === 'password' ? 'Password' : 'OTP'}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );

  const credentialForm = (
    <>
      {modeTabs}
      <DarkField
        label={mode === 'otp' ? 'Phone or email' : 'Work email or phone'}
        value={identifier}
        onChangeText={setIdentifier}
        placeholder={mode === 'otp' ? '07XX XXX XXX' : 'you@school.edu'}
        icon="person-outline"
        autoCapitalize="none"
        keyboardType={mode === 'otp' ? 'phone-pad' : 'email-address'}
        editable={!busy}
      />
      {mode === 'password' ? (
        <>
          <DarkField
            label="Password"
            value={password}
            onChangeText={setPassword}
            placeholder="••••••••"
            icon="lock-closed-outline"
            secureTextEntry={!showPassword}
            autoCapitalize="none"
            editable={!busy}
            right={
              <Pressable onPress={() => setShowPassword((v) => !v)} hitSlop={8}>
                <Ionicons
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={20}
                  color="rgba(255,255,255,0.45)"
                />
              </Pressable>
            }
            onSubmitEditing={handlePasswordSubmit}
          />
          <Pressable
            style={[styles.rememberRow, { marginBottom: spacing.lg }]}
            onPress={() => setRemember((v) => !v)}
          >
            <View
              style={{
                width: 22,
                height: 22,
                borderRadius: 6,
                borderWidth: 2,
                borderColor: remember ? colors.primaryOnDark : 'rgba(255,255,255,0.35)',
                backgroundColor: remember ? colors.primary : 'transparent',
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: spacing.sm,
              }}
            >
              {remember ? <Ionicons name="checkmark" size={14} color="#fff" /> : null}
            </View>
            <Text style={{ color: 'rgba(255,255,255,0.7)', fontSize: typography.body.fontSize }}>
              Keep me signed in
            </Text>
          </Pressable>
          <Button
            label="Sign in"
            onPress={handlePasswordSubmit}
            loading={submitting}
            disabled={!canPasswordSubmit}
          />
        </>
      ) : (
        <>
          {otpSent ? (
            <DarkField
              label="6-digit code"
              value={otpCode}
              onChangeText={(t) => setOtpCode(t.replace(/\D/g, '').slice(0, 6))}
              placeholder="000000"
              icon="keypad-outline"
              keyboardType="number-pad"
              editable={!busy}
              onSubmitEditing={handleVerifyOtp}
            />
          ) : null}
          <Button
            label={otpSent ? 'Verify & sign in' : 'Send OTP'}
            onPress={otpSent ? handleVerifyOtp : handleRequestOtp}
            loading={submitting}
            disabled={otpSent ? !canVerifyOtp : !canRequestOtp}
          />
          {otpSent ? (
            <Pressable onPress={handleRequestOtp} disabled={busy} style={{ marginTop: spacing.sm }}>
              <Text style={{ color: colors.primaryOnDark, textAlign: 'center', fontWeight: '600' }}>
                Resend code
              </Text>
            </Pressable>
          ) : null}
        </>
      )}
    </>
  );

  const sheet = (
    <View
      style={[
        styles.sheet,
        {
          backgroundColor: 'rgba(12,16,24,0.94)',
          borderTopLeftRadius: radius.xl,
          borderTopRightRadius: radius.xl,
          paddingTop: spacing.xl,
          paddingHorizontal: spacing.lg,
          paddingBottom: insets.bottom + spacing.xl,
          borderColor: 'rgba(255,255,255,0.1)',
        },
      ]}
    >
      <View style={[styles.handle, { backgroundColor: 'rgba(255,255,255,0.28)', borderRadius: radius.full }]} />
      <Text
        style={{
          color: '#fff',
          fontSize: typography.headlineLarge.fontSize,
          fontWeight: '800',
          marginBottom: spacing.xs,
        }}
      >
        {bioFirst ? 'Welcome back' : 'Sign in'}
      </Text>
      <Text
        style={{
          color: 'rgba(255,255,255,0.65)',
          fontSize: typography.body.fontSize,
          marginBottom: spacing.lg,
        }}
      >
        {bioFirst
          ? `Unlock with ${typeLabel} — no password needed`
          : 'Password or one-time code to manage your school'}
      </Text>

      {announcements.length > 0 ? (
        <View
          style={{
            marginBottom: spacing.md,
            padding: spacing.mdSm,
            borderRadius: radius.control,
            backgroundColor: 'rgba(75,159,255,0.12)',
            borderWidth: StyleSheet.hairlineWidth,
            borderColor: 'rgba(75,159,255,0.35)',
          }}
        >
          <Text style={{ color: '#93c5fd', fontWeight: '700', marginBottom: spacing.xs }}>
            Announcements
          </Text>
          {announcements.map((a) => (
            <View key={a.id} style={{ marginBottom: spacing.xs }}>
              <Text style={{ color: '#fff', fontWeight: '600' }}>{a.title}</Text>
              <Text style={{ color: 'rgba(255,255,255,0.65)', fontSize: typography.caption.fontSize }}>
                {a.content}
              </Text>
            </View>
          ))}
        </View>
      ) : null}

      {error ? (
        <View
          style={[
            styles.errorBanner,
            {
              backgroundColor: 'rgba(220,38,38,0.18)',
              borderColor: colors.error,
              borderRadius: radius.control,
              padding: spacing.mdSm,
              marginBottom: spacing.md,
            },
          ]}
        >
          <Ionicons name="alert-circle" size={18} color={colors.error} />
          <Text
            style={{
              color: '#fecaca',
              fontSize: typography.caption.fontSize,
              flex: 1,
              marginLeft: spacing.sm,
            }}
          >
            {error}
          </Text>
        </View>
      ) : null}

      {bioFirst ? (
        <>
          <Pressable
            onPress={handleBiometricUnlock}
            disabled={busy}
            style={({ pressed }) => [
              styles.bioPrimary,
              {
                borderRadius: radius.control,
                backgroundColor: colors.primary,
                opacity: busy ? 0.5 : pressed ? 0.9 : 1,
              },
            ]}
          >
            {biometricSubmitting ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <>
                <Soft3DIcon name="shield-outline" size={36} />
                <Text
                  style={{
                    color: '#fff',
                    fontWeight: '700',
                    fontSize: typography.button.fontSize,
                    marginLeft: spacing.sm,
                  }}
                >
                  Unlock with {typeLabel}
                </Text>
              </>
            )}
          </Pressable>
          <View style={{ marginTop: spacing.lg, width: '100%' }}>
            <PinUnlockPanel onUsePassword={() => { setPreferPassword(true); setShowCredentialForm(true); }} />
          </View>
          <Pressable
            onPress={() => { setShowCredentialForm(true); setPreferPassword(true); }}
            style={{ marginTop: spacing.md, alignItems: 'center' }}
          >
            <Text style={{ color: 'rgba(255,255,255,0.7)', fontWeight: '600' }}>
              Sign in with a different account
            </Text>
          </Pressable>
        </>
      ) : (
        <>
          {!preferPassword ? (
            <PinUnlockPanel onUsePassword={() => setPreferPassword(true)} />
          ) : null}
          {credentialForm}
          {unlockAvailable ? (
            <Pressable
              onPress={handleBiometricUnlock}
              disabled={busy}
              style={({ pressed }) => [
                styles.bioBtn,
                {
                  borderRadius: radius.control,
                  borderColor: 'rgba(255,255,255,0.35)',
                  marginTop: spacing.md,
                  opacity: busy ? 0.5 : pressed ? 0.85 : 1,
                },
              ]}
            >
              {biometricSubmitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <Ionicons name="finger-print" size={20} color="#fff" style={{ marginRight: 8 }} />
                  <Text style={{ color: '#fff', fontWeight: '600', fontSize: typography.button.fontSize }}>
                    Continue with {typeLabel}
                  </Text>
                </>
              )}
            </Pressable>
          ) : null}
        </>
      )}
    </View>
  );

  const hero = (
    <View
      style={{
        flex: 1,
        justifyContent: 'flex-end',
        paddingHorizontal: spacing.lg,
        paddingBottom: spacing.xl,
        paddingTop: insets.top + spacing.lg,
      }}
    >
      {brandingLoading ? (
        <ActivityIndicator color="#fff" style={{ marginBottom: spacing.md }} />
      ) : logoUrl && !logoFailed ? (
        <Image
          source={{ uri: logoUrl }}
          style={{ width: 72, height: 72, borderRadius: 18, marginBottom: spacing.md, backgroundColor: '#fff' }}
          onError={() => setLogoFailed(true)}
        />
      ) : (
        <View
          style={{
            width: 72,
            height: 72,
            borderRadius: 20,
            backgroundColor: 'rgba(255,255,255,0.15)',
            alignItems: 'center',
            justifyContent: 'center',
            marginBottom: spacing.md,
          }}
        >
          <Soft3DIcon name="school" size={48} />
        </View>
      )}
      <Text
        style={{
          color: '#fff',
          fontSize: typography.displayLarge.fontSize,
          lineHeight: typography.displayLarge.lineHeight,
          fontWeight: '800',
          letterSpacing: -0.5,
        }}
        numberOfLines={2}
      >
        {schoolName}
      </Text>
      <Text style={{ color: 'rgba(255,255,255,0.78)', fontSize: typography.bodyLarge.fontSize, marginTop: spacing.sm }}>
        School management, built for leaders
      </Text>
    </View>
  );

  const content = (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView
        contentContainerStyle={{ flexGrow: 1 }}
        keyboardShouldPersistTaps="handled"
        bounces={false}
        showsVerticalScrollIndicator={false}
      >
        <View style={{ minHeight: 280 }}>{hero}</View>
        {sheet}
      </ScrollView>
    </KeyboardAvoidingView>
  );

  return (
    <ScreenContainer edges={[]} scroll={false} clearFloatingTabBar={false} style={styles.transparent}>
      <StatusBar style="light" />
      {showBackground ? (
        <ImageBackground
          source={{ uri: loginBackgroundUrl! }}
          style={styles.flex}
          resizeMode="cover"
          onError={() => setBgFailed(true)}
        >
          <LinearGradient
            colors={['rgba(0,0,0,0.25)', 'rgba(12,16,24,0.55)', 'rgba(12,16,24,0.92)']}
            style={StyleSheet.absoluteFillObject}
          />
          {content}
        </ImageBackground>
      ) : (
        <LinearGradient colors={gradientStops} start={{ x: 0.1, y: 0 }} end={{ x: 0.9, y: 1 }} style={styles.flex}>
          {content}
        </LinearGradient>
      )}
    </ScreenContainer>
  );
};

function DarkField({
  label,
  icon,
  right,
  ...props
}: {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  right?: React.ReactNode;
} & TextInputProps) {
  const { spacing, typography, radius } = useTheme();
  const [focused, setFocused] = useState(false);
  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: 'rgba(255,255,255,0.55)',
          fontSize: typography.label.fontSize,
          fontWeight: typography.label.fontWeight,
          marginBottom: spacing.xs,
        }}
      >
        {label}
      </Text>
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          borderWidth: 1,
          borderColor: focused ? '#4B9FFF' : 'rgba(255,255,255,0.14)',
          borderRadius: radius.control,
          backgroundColor: 'rgba(255,255,255,0.06)',
          paddingHorizontal: spacing.mdSm,
          minHeight: 52,
        }}
      >
        <Ionicons name={icon} size={18} color="rgba(255,255,255,0.45)" style={{ marginRight: spacing.sm }} />
        <TextInput
          placeholderTextColor="rgba(255,255,255,0.35)"
          selectionColor="#4B9FFF"
          {...props}
          onFocus={(e) => {
            setFocused(true);
            props.onFocus?.(e);
          }}
          onBlur={(e) => {
            setFocused(false);
            props.onBlur?.(e);
          }}
          style={{
            flex: 1,
            color: '#fff',
            fontSize: typography.bodyLarge.fontSize,
            paddingVertical: spacing.mdSm,
          }}
        />
        {right}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  transparent: { backgroundColor: 'transparent' },
  sheet: { borderTopWidth: StyleSheet.hairlineWidth },
  handle: { alignSelf: 'center', width: 40, height: 4, marginBottom: 16 },
  errorBanner: { flexDirection: 'row', alignItems: 'center', borderWidth: 1 },
  rememberRow: { flexDirection: 'row', alignItems: 'center' },
  bioBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 48,
    borderWidth: 1,
  },
  bioPrimary: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 56,
    paddingHorizontal: 16,
  },
  modeRow: { flexDirection: 'row' },
  modeChip: { flex: 1, alignItems: 'center', paddingVertical: 10 },
});
