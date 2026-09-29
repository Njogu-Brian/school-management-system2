import {
  GOOGLE_ANDROID_CLIENT_ID,
  GOOGLE_IOS_CLIENT_ID,
  GOOGLE_WEB_CLIENT_ID,
  hasGoogleOAuthConfig,
  useAuth,
} from '@erp/core';
import { AccentIcon, Button, GoogleSignInButton, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Platform, StyleSheet, Text } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

/**
 * Shown after password/OTP login when the server asks the user to link Google.
 * Skip is always available for this session.
 */
export const LinkGooglePromptScreen: React.FC = () => {
  const { completeGoogleLinkEnrollment, skipGoogleLinkEnrollment, user } = useAuth();
  const { palette, spacing, typography } = useTheme();
  const [busy, setBusy] = useState(false);
  const configured = hasGoogleOAuthConfig(Platform.OS);

  return (
    <ScreenContainer
      edges={['top', 'bottom']}
      contentContainerStyle={[styles.content, { paddingHorizontal: spacing.lg }]}
    >
      <AccentIcon name="link" tone="blue" size={88} iconSize={40} style={{ marginBottom: spacing.lg }} />
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
        Link Google?
      </Text>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.body.fontSize,
          lineHeight: typography.body.lineHeight,
          textAlign: 'center',
          marginBottom: spacing.lg,
        }}
      >
        Connect the Google account that matches your school email
        {user?.email ? ` (${user.email})` : ''} so you can sign in faster next time. You can skip
        and keep using password or OTP.
      </Text>

      {configured ? (
        <GoogleSignInButton
          clientIds={{
            androidClientId: GOOGLE_ANDROID_CLIENT_ID,
            iosClientId: GOOGLE_IOS_CLIENT_ID,
            webClientId: GOOGLE_WEB_CLIENT_ID,
          }}
          configured
          disabled={busy}
          label="Continue with Google"
          variant="primary"
          onIdToken={async (idToken) => {
            setBusy(true);
            try {
              await completeGoogleLinkEnrollment(idToken);
              showSuccess('Google linked', 'You can use Continue with Google next time.');
            } catch (err) {
              showError(
                'Could not link Google',
                err instanceof Error ? err.message : 'Try again or skip for now.',
              );
            } finally {
              setBusy(false);
            }
          }}
        />
      ) : (
        <Text style={{ color: palette.textMuted, textAlign: 'center', marginBottom: spacing.md }}>
          Google sign-in is not configured on this build. You can skip and continue.
        </Text>
      )}

      <Button
        label="Skip for now"
        variant="ghost"
        onPress={skipGoogleLinkEnrollment}
        disabled={busy}
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
