import { Button } from '../primitives/Button';
import { useTheme } from '../theme';
import Constants from 'expo-constants';
import * as AuthSession from 'expo-auth-session';
import * as Google from 'expo-auth-session/providers/google';
import * as WebBrowser from 'expo-web-browser';
import React, { useEffect } from 'react';
import { Platform, Text, View } from 'react-native';

WebBrowser.maybeCompleteAuthSession();

export type GoogleSignInClientIds = {
  androidClientId?: string;
  iosClientId?: string;
  webClientId?: string;
};

type Props = {
  clientIds: GoogleSignInClientIds;
  /** Exchange Google ID token (login or link). */
  onIdToken: (idToken: string) => Promise<void>;
  label?: string;
  disabled?: boolean;
  variant?: 'primary' | 'secondary' | 'ghost';
  /** When false, render nothing (OAuth not configured). */
  configured?: boolean;
};

function platformClientIdReady(clientIds: GoogleSignInClientIds): boolean {
  if (Platform.OS === 'android') {
    return Boolean(clientIds.androidClientId?.trim());
  }
  if (Platform.OS === 'ios') {
    return Boolean(clientIds.iosClientId?.trim() || clientIds.webClientId?.trim());
  }
  return Boolean(clientIds.webClientId?.trim());
}

/** Expo Go cannot complete Google OAuth (redirect_uri_mismatch). Needs a real APK/IPA. */
function isExpoGo(): boolean {
  return Constants.appOwnership === 'expo';
}

/**
 * Continues with Google via expo-auth-session ID token.
 * Pass client IDs from env (EXPO_PUBLIC_GOOGLE_*). See docs/GOOGLE_MAPS_AND_SIGNIN_SETUP.md.
 *
 * Outer wrapper avoids calling `useIdTokenAuthRequest` when Android/iOS client IDs
 * are missing — that hook throws and would crash the login screen.
 */
export const GoogleSignInButton: React.FC<Props> = (props) => {
  const ready = props.configured !== false && platformClientIdReady(props.clientIds);
  if (!ready) {
    return null;
  }
  if (isExpoGo()) {
    return <GoogleSignInExpoGoNotice />;
  }
  return <GoogleSignInButtonConfigured {...props} />;
};

const GoogleSignInExpoGoNotice: React.FC = () => {
  const { palette, spacing, typography } = useTheme();
  return (
    <View
      style={{
        gap: spacing.xs,
        padding: spacing.md,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: palette.border,
        backgroundColor: palette.surface,
      }}
    >
      <Text style={{ color: palette.textPrimary, fontWeight: '700', textAlign: 'center' }}>
        Continue with Google
      </Text>
      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.caption.fontSize,
          textAlign: 'center',
          lineHeight: 18,
        }}
      >
        Google sign-in does not work inside Expo Go (Google blocks the redirect). Use a preview or
        Play Store build of this app, or sign in with password / OTP here.
      </Text>
    </View>
  );
};

const GoogleSignInButtonConfigured: React.FC<Props> = ({
  clientIds,
  onIdToken,
  label = 'Continue with Google',
  disabled,
  variant = 'secondary',
}) => {
  const { palette, spacing, typography } = useTheme();

  // Standalone / dev-client: scheme from app.config (e.g. royalkingsusers:/oauthredirect).
  // Must match the Android/iOS OAuth client package — never works in Expo Go.
  const redirectUri = AuthSession.makeRedirectUri({
    scheme: Constants.expoConfig?.scheme as string | undefined,
    path: 'oauthredirect',
  });

  const [request, response, promptAsync] = Google.useIdTokenAuthRequest({
    androidClientId: clientIds.androidClientId || undefined,
    iosClientId: clientIds.iosClientId || undefined,
    webClientId: clientIds.webClientId || undefined,
    // Prefer Web client as ID token audience when present.
    clientId: clientIds.webClientId || clientIds.androidClientId || undefined,
    redirectUri,
  });

  useEffect(() => {
    if (response?.type !== 'success') return;
    const idToken =
      response.params.id_token ??
      (response.authentication as { idToken?: string } | null)?.idToken;
    if (!idToken) return;
    void onIdToken(idToken);
  }, [response, onIdToken]);

  return (
    <View style={{ gap: spacing.xs }}>
      <Button
        label={label}
        variant={variant}
        disabled={disabled || !request}
        onPress={() => void promptAsync()}
      />
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, textAlign: 'center' }}>
        Uses your existing school account email — no new accounts are created.
      </Text>
    </View>
  );
};
