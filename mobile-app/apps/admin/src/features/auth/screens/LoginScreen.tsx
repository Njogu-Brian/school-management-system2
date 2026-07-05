import { useAuth, useBiometricAuth, useBranding } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
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
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * Admin login — branded hero + elevated card (aligned with main school ERP app).
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
  const { palette, colors, spacing, fontSizes, radius } = useTheme();
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
    const secondary = branding?.colors?.secondary ?? colors.primary;
    return [primary, primary, secondary];
  }, [branding?.colors, colorOverrides.primary, colors.primary]);

  const handleSubmit = async (): Promise<void> => {
    if (!canSubmit) return;
    try {
      await login({ identifier: identifier.trim(), password, remember });
    } catch {
      /* surfaced via auth error state */
    }
  };

  const handleBiometricUnlock = async (): Promise<void> => {
    if (isLocked) {
      Alert.alert(
        'Biometric sign-in locked',
        'Sign in with your email and password. You can use biometrics again after a successful sign-in.',
      );
      return;
    }
    try {
      await unlock();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Biometric unlock failed.';
      Alert.alert('Unlock failed', message);
      await refreshBiometric();
    }
  };

  const cardContent = (
    <View
      style={[
        styles.card,
        {
          backgroundColor: 'rgba(255,255,255,0.97)',
          borderColor: `${colors.primary}22`,
          borderRadius: radius.lg + 4,
        },
      ]}
    >
      <Text style={[styles.cardTitle, { color: palette.textPrimary }]}>Welcome back</Text>
      <Text style={[styles.cardSubtitle, { color: palette.textSecondary, fontSize: fontSizes.sm }]}>
        Use work email or phone number
      </Text>

      {error ? (
        <View style={[styles.errorBanner, { backgroundColor: `${colors.error}14`, borderColor: colors.error }]}>
          <Ionicons name="alert-circle" size={18} color={colors.error} />
          <Text style={{ color: colors.error, fontSize: fontSizes.sm, flex: 1, marginLeft: 8 }}>{error}</Text>
        </View>
      ) : null}

      <LabeledInput
        label="Work email or phone"
        value={identifier}
        onChangeText={setIdentifier}
        placeholder="you@school.edu or +2547..."
        icon="person-outline"
        autoCapitalize="none"
        keyboardType="email-address"
        editable={!busy}
        palette={palette}
        colors={colors}
        radius={radius.control}
      />

      <LabeledInput
        label="Password"
        value={password}
        onChangeText={setPassword}
        placeholder="••••••••"
        icon="lock-closed-outline"
        secureTextEntry={!showPassword}
        autoCapitalize="none"
        editable={!busy}
        palette={palette}
        colors={colors}
        radius={radius.control}
        right={
          <Pressable onPress={() => setShowPassword((v) => !v)} hitSlop={8}>
            <Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color={palette.textMuted} />
          </Pressable>
        }
        onSubmitEditing={handleSubmit}
      />

      <View style={styles.row}>
        <Pressable style={styles.rememberRow} onPress={() => setRemember((v) => !v)} disabled={busy}>
          <View
            style={[
              styles.checkbox,
              {
                borderColor: remember ? colors.primary : palette.border,
                backgroundColor: remember ? colors.primary : 'transparent',
              },
            ]}
          >
            {remember ? <Text style={styles.checkmark}>✓</Text> : null}
          </View>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>Remember me</Text>
        </Pressable>
      </View>

      <Button label="Sign in" onPress={handleSubmit} loading={submitting} disabled={!canSubmit} />

      {unlockAvailable ? (
        <Button
          label={`Login with ${typeLabel}`}
          variant="secondary"
          onPress={handleBiometricUnlock}
          loading={biometricSubmitting}
          disabled={busy}
          style={{ marginTop: spacing.sm }}
        />
      ) : null}
    </View>
  );

  const hero = (
    <View style={styles.hero}>
      {brandingLoading ? (
        <ActivityIndicator color="#fff" style={{ marginBottom: spacing.md }} />
      ) : logoUrl && !logoFailed ? (
        <View style={styles.logoRing}>
          <Image source={{ uri: logoUrl }} style={styles.logoImage} onError={() => setLogoFailed(true)} />
        </View>
      ) : (
        <LinearGradient colors={gradientStops} style={styles.logoFallback}>
          <Ionicons name="school" size={40} color="#fff" />
        </LinearGradient>
      )}
      <Text style={styles.brandTitle} numberOfLines={2}>
        {schoolName}
      </Text>
      <Text style={styles.brandTagline}>Sign in to your workspace</Text>
    </View>
  );

  const body = (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.flex}
      keyboardVerticalOffset={Platform.OS === 'ios' ? insets.top : 0}
    >
      <ScrollView
        contentContainerStyle={[
          styles.scroll,
          { paddingTop: Math.max(insets.top, spacing.md), paddingBottom: insets.bottom + spacing.xl },
        ]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        automaticallyAdjustKeyboardInsets
      >
        {hero}
        {cardContent}
      </ScrollView>
    </KeyboardAvoidingView>
  );

  return (
    <ScreenContainer edges={[]} scroll={false} style={styles.flex}>
      <StatusBar style="light" />
      {showBackground ? (
        <ImageBackground
          source={{ uri: loginBackgroundUrl! }}
          style={styles.flex}
          resizeMode="cover"
          onError={() => setBgFailed(true)}
        >
          <LinearGradient
            colors={['rgba(0,0,0,0.45)', 'rgba(30,0,50,0.75)', 'rgba(20,0,40,0.85)']}
            style={StyleSheet.absoluteFillObject}
          />
          {body}
        </ImageBackground>
      ) : (
        <LinearGradient colors={gradientStops} start={{ x: 0, y: 0 }} end={{ x: 0.4, y: 1 }} style={styles.flex}>
          {body}
        </LinearGradient>
      )}
    </ScreenContainer>
  );
};

function LabeledInput({
  label,
  icon,
  right,
  palette,
  colors,
  radius,
  ...props
}: {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  right?: React.ReactNode;
  palette: { textSecondary: string; textPrimary: string; textMuted: string; borderSubtle: string };
  colors: { primary: string };
  radius: number;
} & React.ComponentProps<typeof TextInput>) {
  const [focused, setFocused] = useState(false);
  return (
    <View style={{ marginBottom: 14 }}>
      <Text style={{ color: palette.textSecondary, fontSize: 13, marginBottom: 6, fontWeight: '500' }}>{label}</Text>
      <View
        style={[
          styles.inputWrap,
          {
            borderColor: focused ? colors.primary : palette.borderSubtle,
            borderRadius: radius,
            backgroundColor: '#fff',
          },
        ]}
      >
        <Ionicons name={icon} size={18} color={palette.textMuted} style={{ marginRight: 10 }} />
        <TextInput
          placeholderTextColor="#6B7280"
          selectionColor={colors.primary}
          {...props}
          onFocus={(e) => {
            setFocused(true);
            props.onFocus?.(e);
          }}
          onBlur={(e) => {
            setFocused(false);
            props.onBlur?.(e);
          }}
          style={[styles.input, { color: '#111827' }]}
        />
        {right}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  scroll: {
    flexGrow: 1,
    paddingHorizontal: 24,
    justifyContent: 'center',
  },
  hero: {
    alignItems: 'center',
    marginBottom: 28,
  },
  logoRing: {
    padding: 4,
    borderRadius: 28,
    backgroundColor: 'rgba(255,255,255,0.95)',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.2,
    shadowRadius: 16,
    elevation: 8,
  },
  logoImage: {
    width: 80,
    height: 80,
    borderRadius: 22,
    resizeMode: 'contain',
  },
  logoFallback: {
    width: 88,
    height: 88,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  brandTitle: {
    color: '#fff',
    fontSize: 26,
    fontWeight: '800',
    textAlign: 'center',
    letterSpacing: -0.3,
    textShadowColor: 'rgba(0,0,0,0.25)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  brandTagline: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 15,
    marginTop: 6,
    fontWeight: '500',
    textAlign: 'center',
  },
  card: {
    borderWidth: 1,
    paddingVertical: 24,
    paddingHorizontal: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.12,
    shadowRadius: 24,
    elevation: 6,
  },
  cardTitle: {
    fontSize: 22,
    fontWeight: '700',
  },
  cardSubtitle: {
    marginTop: 4,
    marginBottom: 20,
  },
  errorBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderRadius: 10,
    padding: 12,
    marginBottom: 14,
  },
  inputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    paddingHorizontal: 14,
    minHeight: 50,
  },
  input: {
    flex: 1,
    fontSize: 16,
    paddingVertical: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  rememberRow: { flexDirection: 'row', alignItems: 'center' },
  checkbox: {
    width: 22,
    height: 22,
    borderWidth: 2,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
  },
  checkmark: { color: '#fff', fontSize: 14, fontWeight: '700' },
});
