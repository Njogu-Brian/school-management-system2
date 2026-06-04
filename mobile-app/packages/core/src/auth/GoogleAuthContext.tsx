import React, {
  createContext,
  useCallback,
  useContext,
  useMemo,
} from 'react';
import { Platform } from 'react-native';
import {
  GOOGLE_ANDROID_CLIENT_ID,
  GOOGLE_IOS_CLIENT_ID,
  GOOGLE_WEB_CLIENT_ID,
  hasGoogleOAuthConfig,
} from '../config/env';
import { useAuth } from './AuthContext';

export interface GoogleOAuthConfig {
  androidClientId: string;
  iosClientId: string;
  webClientId: string;
}

export interface GoogleAuthContextValue {
  /** OAuth client IDs are present for the current platform. */
  isConfigured: boolean;
  clientIds: GoogleOAuthConfig;
  /** Exchange a Google ID token for a backend session (delegates to AuthProvider). */
  signInWithIdToken: (idToken: string) => Promise<void>;
  submitting: boolean;
  error: string | null;
}

const GoogleAuthContext = createContext<GoogleAuthContextValue | undefined>(undefined);

/**
 * Google OAuth provider abstraction. UI uses expo-auth-session to obtain an ID token,
 * then calls `signInWithIdToken` which routes through `GoogleAuthProvider` (strategy).
 */
export const GoogleAuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { loginWithGoogleIdToken, submitting, error } = useAuth();

  const clientIds = useMemo<GoogleOAuthConfig>(
    () => ({
      androidClientId: GOOGLE_ANDROID_CLIENT_ID,
      iosClientId: GOOGLE_IOS_CLIENT_ID,
      webClientId: GOOGLE_WEB_CLIENT_ID,
    }),
    [],
  );

  const isConfigured = hasGoogleOAuthConfig(Platform.OS);

  const signInWithIdToken = useCallback(
    (idToken: string) => loginWithGoogleIdToken(idToken),
    [loginWithGoogleIdToken],
  );

  const value = useMemo<GoogleAuthContextValue>(
    () => ({
      isConfigured,
      clientIds,
      signInWithIdToken,
      submitting,
      error,
    }),
    [isConfigured, clientIds, signInWithIdToken, submitting, error],
  );

  return <GoogleAuthContext.Provider value={value}>{children}</GoogleAuthContext.Provider>;
};

export function useGoogleAuth(): GoogleAuthContextValue {
  const ctx = useContext(GoogleAuthContext);
  if (!ctx) {
    throw new Error('useGoogleAuth must be used within a GoogleAuthProvider');
  }
  return ctx;
}
