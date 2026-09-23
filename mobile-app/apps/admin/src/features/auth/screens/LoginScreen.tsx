import {
  getRememberedUsername,
  getRememberedFirstName,
  hasPinUnlockAvailable,
  API_BASE_URL,
  authApi,
  useAuth,
  useBiometricAuth,
  useBranding,
} from '@erp/core';
import { Button, ForgotPasswordForm, KeyboardScrollProvider, Soft3DIcon, buildLoginChrome, useAdaptiveLayout, useEnsureInputVisible, useKeyboardHeight, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useMemo, useRef, useState } from 'react';
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
  type ScrollView as ScrollViewType,
  type TextInputProps,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { PinUnlockPanel } from './PinUnlockPanel';

type AuthMode = 'password' | 'otp';
type UnlockSurface = 'quick' | 'pin' | 'password';

type LoginAnnouncement = { id: number; title: string; content: string };

/**
 * Flagship login — school-branded hero, solid page fill, white sheet (stable across OTP/password).
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
  const { isTablet, formMaxWidth } = useAdaptiveLayout();
  const scrollRef = useRef<ScrollViewType>(null);
  const keyboardHeight = useKeyboardHeight();
  const { schoolName, logoUrl, loginBackgroundUrl, loading: brandingLoading, branding } = useBranding();
  const chrome = useMemo(
    () =>
      buildLoginChrome({
        kind: 'admin',
        primary: branding?.colors?.primary,
        secondary: branding?.colors?.secondary,
      }),
    [branding?.colors?.primary, branding?.colors?.secondary],
  );
  const { accent: NAVY, ink: INK, muted: MUTED, line: LINE, fieldBg: FIELD_BG } = chrome;
  const pageBackground = chrome.pageBg;
  const heroColors = chrome.heroGradient;

  const scrollFieldIntoView = () => {
    // Android resize mode alone often leaves focused fields under the keyboard on this layout.
    requestAnimationFrame(() => {
      scrollRef.current?.scrollToEnd({ animated: true });
    });
  };

  const [logoFailed, setLogoFailed] = useState(false);
  const [bgFailed, setBgFailed] = useState(false);
  const [mode, setMode] = useState<AuthMode>('password');
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [remember, setRemember] = useState(true);
  const [showPassword, setShowPassword] = useState(false);
  const [showForgot, setShowForgot] = useState(false);
  const [unlockSurface, setUnlockSurface] = useState<UnlockSurface>('quick');
  const [pinAvailable, setPinAvailable] = useState(false);
  const [rememberedFirstName, setRememberedFirstNameState] = useState<string | null>(null);
  const [announcements, setAnnouncements] = useState<LoginAnnouncement[]>([]);

  useEffect(() => {
    void refreshBiometric();
  }, [refreshBiometric]);

  useEffect(() => {
    void (async () => {
      const [name, first] = await Promise.all([getRememberedUsername(), getRememberedFirstName()]);
      if (name) setIdentifier(name);
      if (first) {
        setRememberedFirstNameState(first);
      } else if (name) {
        const local = name.includes('@') ? name.split('@')[0] : name;
        const pretty = local.replace(/[._-]+/g, ' ').trim().split(/\s+/)[0];
        if (pretty) setRememberedFirstNameState(pretty.charAt(0).toUpperCase() + pretty.slice(1));
      }
      setPinAvailable(await hasPinUnlockAvailable());
    })();
  }, []);

  // Only set default unlock surface once when biometrics/PIN become available.
  // Do not fight an explicit password / PIN choice (that broke "Sign in with password").
  useEffect(() => {
    if (!((unlockAvailable && !isLocked) || pinAvailable)) return;
    setUnlockSurface((s) => (s === 'password' || s === 'pin' ? s : 'quick'));
  }, [unlockAvailable, isLocked, pinAvailable]);

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
  const canQuickUnlock = (unlockAvailable && !isLocked) || pinAvailable;
  const showQuick = unlockSurface === 'quick' && canQuickUnlock;
  const showPinOnly = unlockSurface === 'pin';

  const greeting = useMemo(() => {
    const hour = new Date().getHours();
    const part =
      hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    if (rememberedFirstName) return `${part}, ${rememberedFirstName}`;
    return part;
  }, [rememberedFirstName]);

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
        'Sign in with your password or PIN. You can use biometrics again after a successful sign-in.',
      );
      return;
    }
    try {
      await unlock();
    } catch (err) {
      const name = err instanceof Error ? err.name : '';
      if (name === 'BiometricCancelledError') {
        return;
      }
      showError('Unlock failed', err instanceof Error ? err.message : 'Biometric unlock failed.');
      await refreshBiometric();
    }
  };

  const modeTabs = (
    <View
      style={[
        styles.modeRow,
        {
          backgroundColor: '#E8EDF3',
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
                backgroundColor: active ? NAVY : 'transparent',
                borderRadius: radius.md,
              },
            ]}
          >
            <Text
              style={{
                color: active ? '#FFFFFF' : INK,
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
      <Field
        label={mode === 'otp' ? 'Phone or email' : 'Username'}
        value={identifier}
        onChangeText={setIdentifier}
        placeholder={mode === 'otp' ? '07XX XXX XXX' : 'you@school.edu'}
        icon="person-outline"
        autoCapitalize="none"
        keyboardType={mode === 'otp' ? 'phone-pad' : 'email-address'}
        editable={!busy}
        onFocus={scrollFieldIntoView}
        accent={NAVY}
      />
      {mode === 'password' ? (
        <>
          <Field
            label="Password"
            value={password}
            onChangeText={setPassword}
            placeholder="••••••••"
            icon="lock-closed-outline"
            secureTextEntry={!showPassword}
            autoCapitalize="none"
            editable={!busy}
            onFocus={scrollFieldIntoView}
            accent={NAVY}
            right={
              <Pressable onPress={() => setShowPassword((v) => !v)} hitSlop={8}>
                <Ionicons
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={20}
                  color={MUTED}
                />
              </Pressable>
            }
            onSubmitEditing={handlePasswordSubmit}
          />
          <Pressable
            style={[styles.rememberRow, { marginBottom: spacing.sm }]}
            onPress={() => setRemember((v) => !v)}
          >
            <View
              style={{
                width: 22,
                height: 22,
                borderRadius: 6,
                borderWidth: 2,
                borderColor: remember ? NAVY : LINE,
                backgroundColor: remember ? NAVY : FIELD_BG,
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: spacing.sm,
              }}
            >
              {remember ? <Ionicons name="checkmark" size={14} color="#fff" /> : null}
            </View>
            <Text style={{ color: INK, fontSize: typography.body.fontSize }}>
              Keep me signed in
            </Text>
          </Pressable>
          <Pressable onPress={() => setShowForgot(true)} style={{ marginBottom: spacing.lg }}>
            <Text style={{ color: NAVY, fontWeight: '700' }}>Forgot password?</Text>
          </Pressable>
          <Button
            label="Sign in"
            onPress={handlePasswordSubmit}
            loading={submitting}
            disabled={!canPasswordSubmit}
          />
          <Pressable
            onPress={() => setUnlockSurface('pin')}
            style={{ marginTop: spacing.md, alignItems: 'center' }}
          >
            <Text style={{ color: NAVY, fontWeight: '700' }}>Sign in with PIN</Text>
          </Pressable>
        </>
      ) : (
        <>
          {otpSent ? (
            <Field
              label="6-digit code"
              value={otpCode}
              onChangeText={(t) => setOtpCode(t.replace(/\D/g, '').slice(0, 6))}
              placeholder="000000"
              icon="keypad-outline"
              keyboardType="number-pad"
              editable={!busy}
              onFocus={scrollFieldIntoView}
              onSubmitEditing={handleVerifyOtp}
              accent={NAVY}
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
              <Text style={{ color: NAVY, textAlign: 'center', fontWeight: '700' }}>
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
          backgroundColor: chrome.sheetBg,
          borderWidth: 1,
          borderTopLeftRadius: radius.xl,
          borderTopRightRadius: radius.xl,
          borderBottomLeftRadius: isTablet ? radius.xl : 0,
          borderBottomRightRadius: isTablet ? radius.xl : 0,
          paddingTop: spacing.xl,
          paddingHorizontal: spacing.lg,
          paddingBottom: insets.bottom + spacing.xl,
          borderColor: chrome.sheetBorder,
          alignSelf: isTablet ? 'center' : undefined,
          width: isTablet ? '100%' : undefined,
          maxWidth: isTablet ? formMaxWidth : undefined,
          marginHorizontal: isTablet ? spacing.lg : 0,
          marginBottom: isTablet ? spacing.lg : 0,
          flexGrow: 1,
        },
      ]}
    >
      <View style={[styles.handle, { backgroundColor: LINE, borderRadius: radius.full }]} />
      <Text
        style={{
          color: INK,
          fontSize: typography.headlineLarge.fontSize,
          fontWeight: '800',
          marginBottom: spacing.xs,
        }}
      >
        {showQuick || showPinOnly
          ? greeting
            : rememberedFirstName
              ? greeting
              : chrome.signInTitle}
      </Text>
      <Text
        style={{
          color: MUTED,
          fontSize: typography.body.fontSize,
          marginBottom: spacing.lg,
        }}
      >
        {showPinOnly
          ? 'Enter your app PIN to continue'
          : showQuick
            ? unlockAvailable && !isLocked
              ? `Unlock with ${typeLabel} — or use your PIN`
              : 'Unlock with your PIN — no password needed'
            : rememberedFirstName
              ? 'Welcome back — sign in with password or OTP'
              : 'Password or one-time code for school management'}
      </Text>

      {announcements.length > 0 ? (
        <View
          style={{
            marginBottom: spacing.md,
            padding: spacing.mdSm,
            borderRadius: radius.control,
            backgroundColor: '#F8FAFC',
            borderWidth: 1,
            borderColor: LINE,
          }}
        >
          <Text style={{ color: NAVY, fontWeight: '700', marginBottom: spacing.xs }}>
            Announcements
          </Text>
          {announcements.map((a) => (
            <View key={a.id} style={{ marginBottom: spacing.xs }}>
              <Text style={{ color: INK, fontWeight: '600' }}>{a.title}</Text>
              <Text style={{ color: MUTED, fontSize: typography.caption.fontSize }}>
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

      {showQuick ? (
        <>
          {unlockAvailable && !isLocked ? (
            <Pressable
              onPress={handleBiometricUnlock}
              disabled={busy}
              style={({ pressed }) => [
                styles.bioPrimary,
                {
                  borderRadius: radius.control,
                  backgroundColor: NAVY,
                  opacity: busy ? 0.5 : pressed ? 0.9 : 1,
                },
              ]}
            >
              {biometricSubmitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <Ionicons name="finger-print" size={28} color="#fff" />
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
          ) : null}
          {pinAvailable ? (
            <Pressable
              onPress={() => setUnlockSurface('pin')}
              style={({ pressed }) => [
                styles.bioBtn,
                {
                  borderRadius: radius.control,
                  borderColor: LINE,
                  marginTop: spacing.md,
                  opacity: pressed ? 0.85 : 1,
                },
              ]}
            >
              <Ionicons name="keypad-outline" size={20} color={NAVY} style={{ marginRight: 8 }} />
              <Text style={{ color: NAVY, fontWeight: '700', fontSize: typography.button.fontSize }}>
                Unlock with PIN
              </Text>
            </Pressable>
          ) : null}
          <Pressable
            onPress={() => setUnlockSurface('password')}
            style={{ marginTop: spacing.md, alignItems: 'center' }}
          >
            <Text style={{ color: NAVY, fontWeight: '700' }}>
              Sign in with password
            </Text>
          </Pressable>
        </>
      ) : showPinOnly ? (
        <>
          <PinUnlockPanel
            variant="default"
            onUsePassword={() => setUnlockSurface('password')}
            identifier={identifier}
            onIdentifierChange={setIdentifier}
          />
          {unlockAvailable && !isLocked ? (
            <Pressable
              onPress={() => setUnlockSurface('quick')}
              style={{ marginTop: spacing.md, alignItems: 'center' }}
            >
              <Text style={{ color: NAVY, fontWeight: '700' }}>
                Use {typeLabel} instead
              </Text>
            </Pressable>
          ) : null}
        </>
      ) : (
        <>
          {credentialForm}
          {canQuickUnlock ? (
            <Pressable
              onPress={() => setUnlockSurface('quick')}
              style={{ marginTop: spacing.md, alignItems: 'center' }}
            >
              <Text style={{ color: NAVY, fontWeight: '700' }}>
                Back to quick unlock
              </Text>
            </Pressable>
          ) : null}
        </>
      )}

    </View>
  );

  const hero = (
    <LinearGradient
      colors={heroColors}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={{
        marginTop: insets.top + spacing.md,
        marginHorizontal: spacing.lg,
        marginBottom: spacing.md,
        paddingHorizontal: spacing.lg,
        paddingVertical: spacing.lg,
        borderRadius: radius.xl,
        overflow: 'hidden',
      }}
    >
      <View
        pointerEvents="none"
        style={{
          position: 'absolute',
          top: -40,
          right: -30,
          width: 160,
          height: 160,
          borderRadius: 80,
          backgroundColor: 'rgba(255,255,255,0.12)',
        }}
      />
      <View
        pointerEvents="none"
        style={{
          position: 'absolute',
          bottom: -50,
          left: -20,
          width: 140,
          height: 140,
          borderRadius: 70,
          backgroundColor: 'rgba(255,255,255,0.08)',
        }}
      />
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
      <View
        style={{
          alignSelf: 'flex-start',
          marginTop: spacing.md,
          paddingHorizontal: spacing.mdSm,
          paddingVertical: spacing.xs,
          borderRadius: radius.full,
          backgroundColor: chrome.badgeBg,
          borderWidth: StyleSheet.hairlineWidth,
          borderColor: chrome.badgeBorder,
        }}
      >
        <Text
          style={{
            color: chrome.badgeText,
            fontWeight: '800',
            fontSize: typography.caption.fontSize,
            letterSpacing: 0.6,
          }}
        >
          {chrome.badgeLabel}
        </Text>
      </View>
      <Text style={{ color: 'rgba(255,255,255,0.78)', fontSize: typography.bodyLarge.fontSize, marginTop: spacing.sm }}>
        {chrome.tagline}
      </Text>
    </LinearGradient>
  );

  const content = (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior="padding"
      keyboardVerticalOffset={Platform.OS === 'ios' ? insets.top : 0}
    >
      <KeyboardScrollProvider scrollRef={scrollRef}>
        <ScrollView
          ref={scrollRef}
          contentContainerStyle={{
            flexGrow: 1,
            paddingBottom: Math.max(insets.bottom, 16) + keyboardHeight + spacing.xl,
          }}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          automaticallyAdjustKeyboardInsets
          bounces={false}
          showsVerticalScrollIndicator={false}
        >
          <View>{hero}</View>
          {sheet}
        </ScrollView>
      </KeyboardScrollProvider>
    </KeyboardAvoidingView>
  );

  if (showForgot) {
    return (
      <ForgotPasswordForm
        onBack={() => setShowForgot(false)}
        requestOtp={async (id) => {
          const res = await authApi.requestPasswordResetOtp(id);
          if (!res.success) throw new Error(res.message || 'Could not send the code.');
        }}
        verifyOtp={async (id, code) => {
          const res = await authApi.verifyPasswordResetOtp(id, code);
          if (!res.success || !res.data?.token) throw new Error(res.message || 'Invalid or expired code.');
          return res.data.token;
        }}
        resetPassword={async (payload) => {
          const res = await authApi.resetPassword(payload);
          if (!res.success) throw new Error(res.message || 'Could not reset password.');
          showSuccess('Password reset', 'Sign in with your new password.');
          setShowForgot(false);
        }}
      />
    );
  }

  return (
    <View style={[styles.flex, { backgroundColor: pageBackground }]}>
      <StatusBar style={showBackground ? 'light' : 'dark'} />
      {showBackground ? (
        <ImageBackground
          source={{ uri: loginBackgroundUrl! }}
          style={styles.flex}
          resizeMode="cover"
          onError={() => setBgFailed(true)}
        >
          <LinearGradient
            colors={['rgba(0,0,0,0.25)', 'rgba(12,16,24,0.55)', 'rgba(12,16,24,0.92)']}
            style={StyleSheet.absoluteFill}
          />
          {content}
        </ImageBackground>
      ) : (
        content
      )}
    </View>
  );
};

function Field({
  label,
  icon,
  right,
  accent = '#0F2744',
  ...props
}: {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  right?: React.ReactNode;
  accent?: string;
} & TextInputProps) {
  const { spacing, typography, radius } = useTheme();
  const [focused, setFocused] = useState(false);
  const ensureVisible = useEnsureInputVisible();
  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: '#4B5563',
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
          borderColor: focused ? accent : '#D1D5DB',
          borderRadius: radius.control,
          backgroundColor: '#FFFFFF',
          paddingHorizontal: spacing.mdSm,
          minHeight: 52,
        }}
      >
        <Ionicons name={icon} size={18} color="#4B5563" style={{ marginRight: spacing.sm }} />
        <TextInput
          placeholderTextColor="#6B7280"
          selectionColor={accent}
          {...props}
          onFocus={(e) => {
            setFocused(true);
            props.onFocus?.(e);
            requestAnimationFrame(() => ensureVisible?.());
          }}
          onBlur={(e) => {
            setFocused(false);
            props.onBlur?.(e);
          }}
          style={{
            flex: 1,
            color: '#111827',
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
