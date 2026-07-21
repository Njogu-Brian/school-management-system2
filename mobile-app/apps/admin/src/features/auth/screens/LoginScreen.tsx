import { useAuth, useBiometricAuth, useBranding } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
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
import { showError } from '../../shared/utils/feedback';

/**
 * Flagship login — full-bleed hero, dark glass sheet, brand-first hierarchy.
 */
export const LoginScreen: React.FC = () => {
  const { login, submitting, error } = useAuth();
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
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    void refreshBiometric();
  }, [refreshBiometric]);

  const busy = submitting || biometricSubmitting;
  const canSubmit = identifier.trim().length > 0 && password.length > 0 && !busy;
  const showBackground = Boolean(loginBackgroundUrl) && !bgFailed;

  const gradientStops = useMemo((): [string, string, string] => {
    const primary = branding?.colors?.primary ?? colorOverrides.primary ?? colors.primary;
    return [primary, '#003366', '#0c1018'];
  }, [branding?.colors, colorOverrides.primary, colors.primary]);

  const handleSubmit = async (): Promise<void> => {
    if (!canSubmit) return;
    try {
      await login({ identifier: identifier.trim(), password, remember });
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
      return;
    }
    try {
      await unlock();
    } catch (err) {
      showError('Unlock failed', err instanceof Error ? err.message : 'Biometric unlock failed.');
      await refreshBiometric();
    }
  };

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
        Welcome back
      </Text>
      <Text
        style={{
          color: 'rgba(255,255,255,0.65)',
          fontSize: typography.body.fontSize,
          marginBottom: spacing.lg,
        }}
      >
        Sign in to manage your school
      </Text>

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
          <Text style={{ color: '#fecaca', fontSize: typography.caption.fontSize, flex: 1, marginLeft: spacing.sm }}>
            {error}
          </Text>
        </View>
      ) : null}

      <DarkField
        label="Work email or phone"
        value={identifier}
        onChangeText={setIdentifier}
        placeholder="you@school.edu"
        icon="person-outline"
        autoCapitalize="none"
        keyboardType="email-address"
        editable={!busy}
      />
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
            <Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color="rgba(255,255,255,0.45)" />
          </Pressable>
        }
        onSubmitEditing={handleSubmit}
      />

      <Pressable style={[styles.rememberRow, { marginBottom: spacing.lg }]} onPress={() => setRemember((v) => !v)}>
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
        <Text style={{ color: 'rgba(255,255,255,0.7)', fontSize: typography.body.fontSize }}>Keep me signed in</Text>
      </Pressable>

      <Button label="Sign in" onPress={handleSubmit} loading={submitting} disabled={!canSubmit} />
      {unlockAvailable ? (
        <Pressable
          onPress={handleBiometricUnlock}
          disabled={busy}
          style={({ pressed }) => [
            styles.bioBtn,
            {
              borderRadius: radius.control,
              borderColor: 'rgba(255,255,255,0.35)',
              marginTop: spacing.sm,
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
          <Ionicons name="school" size={36} color="#fff" />
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
        <View style={{ minHeight: 300 }}>{hero}</View>
        {sheet}
      </ScrollView>
    </KeyboardAvoidingView>
  );

  return (
    <ScreenContainer edges={[]} scroll={false} style={styles.transparent}>
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
});
