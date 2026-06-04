import { useGoogleAuth } from '@erp/core';
import { Button, useTheme } from '@erp/ui';
import * as Google from 'expo-auth-session/providers/google';
import * as WebBrowser from 'expo-web-browser';
import React, { useEffect, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

WebBrowser.maybeCompleteAuthSession();

/**
 * Google OAuth button (expo-auth-session). Obtains an ID token client-side and
 * exchanges it via `GoogleSignInStrategy` → `POST /login/google`.
 */
export const GoogleSignInButton: React.FC = () => {
  const { isConfigured, clientIds, signInWithIdToken, submitting, error } = useGoogleAuth();
  const { palette, fontSizes, spacing } = useTheme();
  const [prompting, setPrompting] = useState(false);

  const [, googleResponse, googlePromptAsync] = Google.useIdTokenAuthRequest({
    androidClientId: clientIds.androidClientId || undefined,
    iosClientId: clientIds.iosClientId || undefined,
    webClientId: clientIds.webClientId || undefined,
    selectAccount: true,
  });

  useEffect(() => {
    if (googleResponse?.type !== 'success') {
      return;
    }
    const idToken = googleResponse.params?.id_token;
    if (!idToken) {
      return;
    }
    (async () => {
      try {
        await signInWithIdToken(String(idToken));
      } catch (err) {
        const message = err instanceof Error ? err.message : 'Google sign-in failed.';
        Alert.alert('Google sign-in failed', message);
      }
    })();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [googleResponse]);

  const handlePress = async (): Promise<void> => {
    if (!isConfigured) {
      Alert.alert(
        'Google sign-in not configured',
        'Set EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID (and iOS/web if needed) in the Admin app environment, then restart the dev server.',
      );
      return;
    }
    setPrompting(true);
    try {
      await googlePromptAsync();
    } catch {
      Alert.alert('Google sign-in', 'Could not open Google sign-in.');
    } finally {
      setPrompting(false);
    }
  };

  const loading = submitting || prompting;

  return (
    <View style={{ marginTop: spacing.md }}>
      <Button
        label="Continue with Google"
        variant="secondary"
        onPress={handlePress}
        loading={loading}
        disabled={loading}
      />
      {!isConfigured ? (
        <Text style={[styles.hint, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
          Google OAuth client ID not configured for this build.
        </Text>
      ) : null}
      {error && !submitting ? (
        <Text style={[styles.hint, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
          {error}
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  hint: { marginTop: 8, textAlign: 'center' },
});
