import { useAuth, useBiometricAuth } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

/**
 * Shown once after the first successful password or Google login when the device
 * supports biometrics and the user has not enabled them yet.
 */
export const BiometricEnableScreen: React.FC = () => {
  const { enableBiometrics, skipBiometricEnrollment } = useAuth();
  const { typeLabel } = useBiometricAuth();
  const { palette, colors, spacing, fontSizes } = useTheme();
  const [loading, setLoading] = useState(false);

  const handleEnable = async (): Promise<void> => {
    setLoading(true);
    try {
      await enableBiometrics();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Could not enable biometrics.';
      Alert.alert('Biometrics', message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <View style={[styles.iconWrap, { backgroundColor: `${colors.primary}18` }]}>
        <Ionicons name="finger-print" size={40} color={colors.primary} />
      </View>
      <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
        Enable {typeLabel}?
      </Text>
      <Text style={[styles.body, { color: palette.textSecondary, fontSize: fontSizes.md }]}>
        Sign in faster next time. {typeLabel} only unlocks your existing session on this
        device — you will still need your password or Google account if the session expires.
      </Text>

      <Button
        label={`Enable ${typeLabel}`}
        onPress={handleEnable}
        loading={loading}
        style={{ marginTop: spacing.xl }}
      />
      <Button
        label="Not now"
        variant="ghost"
        onPress={skipBiometricEnrollment}
        disabled={loading}
        style={{ marginTop: spacing.md }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrap: {
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  title: { fontWeight: '700', marginBottom: 12, textAlign: 'center' },
  body: { textAlign: 'center', lineHeight: 22 },
});
