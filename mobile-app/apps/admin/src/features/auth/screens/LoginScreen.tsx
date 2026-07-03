import { useAuth, useBiometricAuth, useBranding } from '@erp/core';
import { Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Image,
  ImageBackground,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';

/**
 * Admin login: email/password and biometric unlock for a saved session.
 * Branding (logo, name, colors, background) is loaded from GET /app-branding.
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
  const { schoolName, logoUrl, loginBackgroundUrl, loading: brandingLoading } = useBranding();
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

  const handleSubmit = async (): Promise<void> => {
    if (!canSubmit) {
      return;
    }
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

  const form = (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={styles.flex}
    >
      <View style={[styles.formCard, showBackground && styles.formCardOverlay, { borderRadius: radius.lg }]}>
        <View style={styles.brand}>
          {brandingLoading ? (
            <ActivityIndicator color={colors.primary} style={{ marginBottom: 16 }} />
          ) : logoUrl && !logoFailed ? (
            <Image
              source={{ uri: logoUrl }}
              style={styles.logoImage}
              onError={() => setLogoFailed(true)}
            />
          ) : (
            <View style={[styles.logo, { backgroundColor: colors.primary }]}>
              <Ionicons name="school" size={32} color={colors.white} />
            </View>
          )}
          <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.xxl }]}>
            {schoolName}
          </Text>
          <Text style={[styles.subtitle, { color: palette.textSecondary, fontSize: fontSizes.sm }]}>
            Sign in to manage your school
          </Text>
        </View>

        {unlockAvailable ? (
          <View style={[styles.biometricCard, { borderColor: palette.border, borderRadius: radius.md }]}>
            <Ionicons name="finger-print" size={28} color={colors.primary} />
            <Text style={[styles.biometricLabel, { color: palette.textPrimary, fontSize: fontSizes.sm }]}>
              Unlock with {typeLabel}
            </Text>
            <Button
              label={`Use ${typeLabel}`}
              variant="secondary"
              onPress={handleBiometricUnlock}
              loading={biometricSubmitting}
              disabled={busy}
              fullWidth={false}
              style={styles.biometricBtn}
            />
          </View>
        ) : null}

        {unlockAvailable ? (
          <View style={styles.dividerRow}>
            <View style={[styles.dividerLine, { backgroundColor: palette.border }]} />
            <Text style={[styles.dividerText, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
              or sign in
            </Text>
            <View style={[styles.dividerLine, { backgroundColor: palette.border }]} />
          </View>
        ) : null}

        {error ? (
          <View
            style={[
              styles.errorBanner,
              { backgroundColor: `${colors.error}1a`, borderColor: colors.error, borderRadius: radius.md },
            ]}
          >
            <Ionicons name="alert-circle" size={18} color={colors.error} />
            <Text style={[styles.errorText, { color: colors.error, fontSize: fontSizes.sm }]}>{error}</Text>
          </View>
        ) : null}

        <TextField
          label="Email or phone"
          value={identifier}
          onChangeText={setIdentifier}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="email-address"
          textContentType="username"
          placeholder="you@school.ac.ke"
          editable={!busy}
          returnKeyType="next"
        />

        <TextField
          label="Password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry={!showPassword}
          autoCapitalize="none"
          autoCorrect={false}
          textContentType="password"
          placeholder="••••••••"
          editable={!busy}
          returnKeyType="go"
          onSubmitEditing={handleSubmit}
        />

        <View style={styles.row}>
          <View style={styles.rememberRow}>
            <Switch
              value={remember}
              onValueChange={setRemember}
              trackColor={{ true: colors.primary, false: palette.border }}
              disabled={busy}
            />
            <Text style={[styles.rememberLabel, { color: palette.textSecondary, fontSize: fontSizes.sm }]}>
              Keep me signed in
            </Text>
          </View>
          <Pressable onPress={() => setShowPassword((v) => !v)} disabled={busy} hitSlop={8}>
            <Text style={{ color: colors.primary, fontSize: fontSizes.sm, fontWeight: '600' }}>
              {showPassword ? 'Hide' : 'Show'}
            </Text>
          </Pressable>
        </View>

        <Button
          label="Sign in"
          onPress={handleSubmit}
          loading={submitting}
          disabled={!canSubmit}
          style={{ marginTop: spacing.md }}
        />
      </View>
    </KeyboardAvoidingView>
  );

  return (
    <ScreenContainer edges={['top', 'bottom']} scroll={false} style={styles.flex}>
      {showBackground ? (
        <ImageBackground
          source={{ uri: loginBackgroundUrl! }}
          style={styles.flex}
          resizeMode="cover"
          onError={() => setBgFailed(true)}
        >
          <View style={styles.bgOverlay} />
          <View style={styles.content}>{form}</View>
        </ImageBackground>
      ) : (
        <View style={[styles.flex, { backgroundColor: palette.background }]}>
          <View style={styles.content}>{form}</View>
        </View>
      )}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  content: {
    flex: 1,
    paddingHorizontal: 24,
    justifyContent: 'center',
  },
  bgOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255,255,255,0.88)',
  },
  formCard: {
    width: '100%',
  },
  formCardOverlay: {
    backgroundColor: 'rgba(255,255,255,0.92)',
    padding: 20,
  },
  brand: {
    alignItems: 'center',
    marginBottom: 24,
  },
  logoImage: {
    width: 72,
    height: 72,
    borderRadius: 18,
    marginBottom: 16,
    resizeMode: 'contain',
  },
  logo: {
    width: 64,
    height: 64,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  title: { fontWeight: '700', textAlign: 'center' },
  subtitle: { marginTop: 4, textAlign: 'center' },
  biometricCard: {
    borderWidth: 1,
    padding: 16,
    alignItems: 'center',
    marginBottom: 8,
  },
  biometricLabel: { marginTop: 8, marginBottom: 12, fontWeight: '600' },
  biometricBtn: { alignSelf: 'center', minWidth: 160 },
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 16,
  },
  dividerLine: { flex: 1, height: StyleSheet.hairlineWidth },
  dividerText: { marginHorizontal: 12, fontWeight: '600' },
  errorBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    padding: 12,
    marginBottom: 16,
  },
  errorText: { marginLeft: 8, flex: 1 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 4,
  },
  rememberRow: { flexDirection: 'row', alignItems: 'center' },
  rememberLabel: { marginLeft: 8 },
});
