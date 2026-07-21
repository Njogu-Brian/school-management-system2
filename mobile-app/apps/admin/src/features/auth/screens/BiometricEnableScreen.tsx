import { useAuth, useBiometricAuth } from '@erp/core';
import { AccentIcon, Button, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { showError } from '../../shared/utils/feedback';

/**
 * Shown once after the first successful password login when the device
 * supports biometrics and the user has not enabled them yet.
 */
export const BiometricEnableScreen: React.FC = () => {
  const { enableBiometrics, skipBiometricEnrollment } = useAuth();
  const { typeLabel } = useBiometricAuth();
  const { palette, spacing, typography } = useTheme();
  const [loading, setLoading] = useState(false);

  const handleEnable = async (): Promise<void> => {
    setLoading(true);
    try {
      await enableBiometrics();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Could not enable biometrics.';
      showError('Biometrics', message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScreenContainer
      edges={['top', 'bottom']}
      contentContainerStyle={[styles.content, { paddingHorizontal: spacing.lg }]}
    >
      <AccentIcon name="finger-print" tone="blue" size={88} iconSize={40} style={{ marginBottom: spacing.lg }} />
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.headline.fontSize,
          fontWeight: typography.headline.fontWeight,
          letterSpacing: typography.headline.letterSpacing,
          marginBottom: spacing.mdSm,
          textAlign: 'center',
        }}
      >
        Enable {typeLabel}?
      </Text>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.body.fontSize,
          lineHeight: typography.body.lineHeight,
          textAlign: 'center',
        }}
      >
        Sign in faster next time. {typeLabel} only unlocks your existing session on this device — you
        will still need your password if the session expires.
      </Text>

      <Button
        label={`Enable ${typeLabel}`}
        onPress={handleEnable}
        loading={loading}
        style={{ marginTop: spacing.xl, alignSelf: 'stretch' }}
      />
      <Button
        label="Not now"
        variant="ghost"
        onPress={skipBiometricEnrollment}
        disabled={loading}
        style={{ marginTop: spacing.md, alignSelf: 'stretch' }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    alignItems: 'center',
    justifyContent: 'center',
  },
});
