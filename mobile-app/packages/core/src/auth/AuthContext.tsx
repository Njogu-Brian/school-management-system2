import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { AppState, AppStateStatus } from 'react-native';
import { authApi } from '../api/auth.api';
import { apiClient } from '../api/client';
import type { ApiError, AuthStatus, GoogleIdentity, LoginCredentials, User } from '../types';
import { getCachedUser, saveUser } from '../storage/authStorage';
import {
  authenticateWithBiometrics,
  canUseBiometrics,
  clearBiometricEnrollment,
  getBiometricAuthBundle,
  getBiometricEnabled,
  saveBiometricAuthBundle,
  setBiometricEnabled,
} from '../storage/biometricStorage';
import { mapApiUser } from './mapUser';
import { parseGoogleIdToken } from './googleIdentity';
import { establishSessionFromResult } from './providers/establishSession';
import { PasswordAuthProvider, toAuthError } from './providers/PasswordAuthProvider';
import { GoogleSignInStrategy } from './providers/GoogleAuthProvider';
import { BiometricUnlockStrategy, BiometricLoginLockedError } from './providers/BiometricAuthProvider';
import type { AuthMethod, AuthProviderResult } from './providers/types';
import { useSession } from './SessionContext';

const AUTH_BOOTSTRAP_TIMEOUT_MS = 12_000;

function withTimeout<T>(promise: Promise<T>, ms: number): Promise<T> {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('AUTH_BOOTSTRAP_TIMEOUT')), ms);
    promise
      .then((value) => {
        clearTimeout(timer);
        resolve(value);
      })
      .catch((err) => {
        clearTimeout(timer);
        reject(err);
      });
  });
}

const passwordProvider = new PasswordAuthProvider();
const googleProvider = new GoogleSignInStrategy();
const biometricProvider = new BiometricUnlockStrategy();

export interface AuthContextValue {
  status: AuthStatus;
  user: User | null;
  /** How the current session was established (password, Google, or biometric unlock). */
  lastAuthMethod: AuthMethod | null;
  /** Google account from the most recent Google sign-in. */
  googleIdentity: GoogleIdentity | null;
  error: string | null;
  submitting: boolean;
  /** Offer biometric enrollment after a successful password/Google login. */
  biometricEnrollmentPending: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  loginWithGoogleIdToken: (idToken: string) => Promise<void>;
  /** Request a login OTP for phone/email identifier. */
  requestLoginOtp: (identifier: string) => Promise<void>;
  /** Verify login OTP and establish a session. */
  verifyLoginOtp: (identifier: string, code: string) => Promise<void>;
  /** Unlock an existing session with device biometrics (no backend login). */
  unlockWithBiometrics: () => Promise<void>;
  dismissBiometricEnrollment: () => void;
  enableBiometrics: () => Promise<void>;
  skipBiometricEnrollment: () => void;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const session = useSession();
  const [status, setStatus] = useState<AuthStatus>('initializing');
  const [user, setUser] = useState<User | null>(null);
  const [lastAuthMethod, setLastAuthMethod] = useState<AuthMethod | null>(null);
  const [googleIdentity, setGoogleIdentity] = useState<GoogleIdentity | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [biometricEnrollmentPending, setBiometricEnrollmentPending] = useState(false);

  const logoutRef = useRef<() => Promise<void>>(async () => {});
  /** Last password/OTP credentials — used when enabling biometrics so unlock can re-login. */
  const lastCredentialsRef = useRef<{ identifier: string; password: string } | null>(null);

  const completeAuth = useCallback(
    async (result: AuthProviderResult, options?: { offerBiometricEnrollment?: boolean }) => {
      await establishSessionFromResult(session, result);
      setUser(result.user);
      setLastAuthMethod(result.method);
      setError(null);

      if (result.method === 'google') {
        const identity =
          (result.user.googleId
            ? {
                sub: result.user.googleId,
                email: result.user.googleEmail ?? undefined,
              }
            : null) ?? null;
        setGoogleIdentity(identity);
      } else if (result.method === 'password' || result.method === 'otp') {
        setGoogleIdentity(null);
      }

      const offerEnrollment = options?.offerBiometricEnrollment ?? result.method !== 'biometric';
      if (offerEnrollment && result.method !== 'biometric') {
        const enabled = await getBiometricEnabled();
        const deviceOk = await canUseBiometrics();
        const existing = await getBiometricAuthBundle();
        const switchedUser =
          Boolean(existing?.userId) && existing!.userId !== result.user.id;

        if (switchedUser) {
          await clearBiometricEnrollment();
          if (deviceOk) {
            setBiometricEnrollmentPending(true);
            setStatus('authenticated');
            return;
          }
        } else if (!enabled && deviceOk) {
          setBiometricEnrollmentPending(true);
          setStatus('authenticated');
          return;
        } else if (enabled && deviceOk) {
          const creds = lastCredentialsRef.current;
          await saveBiometricAuthBundle(result.token, {
            userId: result.user.id,
            identifier: creds?.identifier,
            password: creds?.password,
          });
        }
      }

      setBiometricEnrollmentPending(false);
      setStatus('authenticated');
    },
    [session],
  );

  const logout = useCallback(async () => {
    try {
      if (session.token) {
        await authApi.logout().catch(() => undefined);
      }
    } finally {
      await session.clearSession();
      // Keep biometric enrollment so the next visit can unlock without retyping a password.
      setUser(null);
      setLastAuthMethod(null);
      setGoogleIdentity(null);
      setError(null);
      setBiometricEnrollmentPending(false);
      setStatus('unauthenticated');
    }
  }, [session]);
  logoutRef.current = logout;

  useEffect(() => {
    if (!session.hydrated) {
      return;
    }
    let cancelled = false;

    (async () => {
      if (!session.isValid) {
        if (!cancelled) {
          setUser(null);
          setLastAuthMethod(null);
          setStatus('unauthenticated');
        }
        return;
      }

      try {
        const res = await withTimeout(authApi.getProfile(), AUTH_BOOTSTRAP_TIMEOUT_MS);
        if (cancelled) {
          return;
        }
        if (res.success && res.data) {
          const mapped = mapApiUser(res.data);
          await saveUser(mapped);
          setUser(mapped);
          setStatus('authenticated');
          return;
        }
        setStatus('unauthenticated');
      } catch (err) {
        if (cancelled) {
          return;
        }
        const httpStatus = (err as ApiError)?.status;
        if (httpStatus === 401) {
          await session.clearSession();
          setUser(null);
          setStatus('unauthenticated');
          return;
        }
        const cached = await getCachedUser();
        if (cached) {
          setUser(cached);
          setStatus('authenticated');
        } else {
          setStatus('unauthenticated');
        }
      }
    })();

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [session.hydrated]);

  const runAuth = useCallback(
    async (runner: () => Promise<AuthProviderResult>, offerEnrollment = true) => {
      setSubmitting(true);
      setError(null);
      try {
        const result = await runner();
        await completeAuth(result, { offerBiometricEnrollment: offerEnrollment });
      } catch (err) {
        const message = toAuthError(err);
        setError(message);
        throw err;
      } finally {
        setSubmitting(false);
      }
    },
    [completeAuth],
  );

  const login = useCallback(
    (credentials: LoginCredentials) =>
      runAuth(async () => {
        lastCredentialsRef.current = {
          identifier: credentials.identifier,
          password: credentials.password,
        };
        return passwordProvider.authenticate(credentials);
      }),
    [runAuth],
  );

  const requestLoginOtp = useCallback(async (identifier: string) => {
    setSubmitting(true);
    setError(null);
    try {
      const res = await authApi.requestLoginOtp(identifier.trim());
      if (!res.success) {
        throw new Error(res.message || 'Could not send OTP.');
      }
    } catch (err) {
      const message = toAuthError(err);
      setError(message);
      throw err;
    } finally {
      setSubmitting(false);
    }
  }, []);

  const verifyLoginOtp = useCallback(
    (identifier: string, code: string) =>
      runAuth(async () => {
        const res = await authApi.verifyLoginOtp(identifier.trim(), code.trim());
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Invalid OTP.');
        }
        lastCredentialsRef.current = null;
        return {
          method: 'otp' as const,
          token: res.data.token,
          user: mapApiUser(res.data.user),
          expiresAt: res.data.expires_at ?? null,
          rememberMe: true,
        };
      }),
    [runAuth],
  );

  const loginWithGoogleIdToken = useCallback(
    (idToken: string) =>
      runAuth(async () => {
        const result = await googleProvider.authenticate({ idToken });
        const identity = parseGoogleIdToken(idToken);
        if (identity) {
          setGoogleIdentity(identity);
        }
        return result;
      }),
    [runAuth],
  );

  const unlockWithBiometrics = useCallback(
    () => runAuth(() => biometricProvider.authenticate({}), false),
    [runAuth],
  );

  const dismissBiometricEnrollment = useCallback(() => {
    setBiometricEnrollmentPending(false);
  }, []);

  const enableBiometrics = useCallback(async () => {
    const token = session.token;
    if (!token) {
      throw new Error('No active session to protect with biometrics.');
    }
    const deviceOk = await canUseBiometrics();
    if (!deviceOk) {
      throw new Error('Biometrics are not available on this device.');
    }
    const verified = await authenticateWithBiometrics(
      'Confirm your identity to enable biometric sign-in',
    );
    if (!verified) {
      throw new Error('Biometric verification was cancelled.');
    }
    const creds = lastCredentialsRef.current;
    await setBiometricEnabled(true);
    await saveBiometricAuthBundle(token, {
      userId: user?.id,
      identifier: creds?.identifier,
      password: creds?.password,
    });
    setBiometricEnrollmentPending(false);
  }, [session.token, user?.id]);

  const skipBiometricEnrollment = useCallback(() => {
    setBiometricEnrollmentPending(false);
  }, []);

  const refreshUser = useCallback(async () => {
    const res = await authApi.getProfile();
    if (res.success && res.data) {
      const mapped = mapApiUser(res.data);
      await saveUser(mapped);
      setUser(mapped);
    }
  }, []);

  useEffect(() => {
    apiClient.setOnUnauthorized(async () => {
      const refreshed = await session.refresh();
      if (!refreshed) {
        await logoutRef.current();
      }
    });
    return () => apiClient.setOnUnauthorized(null);
  }, [session]);

  useEffect(() => {
    const onChange = (next: AppStateStatus) => {
      if (next !== 'active') {
        return;
      }
      if (session.token && !session.isValid) {
        void logoutRef.current();
      } else if (session.token) {
        void session.touch();
      }
    };
    const sub = AppState.addEventListener('change', onChange);
    return () => sub.remove();
  }, [session]);

  const value = useMemo<AuthContextValue>(
    () => ({
      status,
      user,
      lastAuthMethod,
      googleIdentity,
      error,
      submitting,
      biometricEnrollmentPending,
      login,
      loginWithGoogleIdToken,
      requestLoginOtp,
      verifyLoginOtp,
      unlockWithBiometrics,
      dismissBiometricEnrollment,
      enableBiometrics,
      skipBiometricEnrollment,
      logout,
      refreshUser,
    }),
    [
      status,
      user,
      lastAuthMethod,
      googleIdentity,
      error,
      submitting,
      biometricEnrollmentPending,
      login,
      loginWithGoogleIdToken,
      requestLoginOtp,
      verifyLoginOtp,
      unlockWithBiometrics,
      dismissBiometricEnrollment,
      enableBiometrics,
      skipBiometricEnrollment,
      logout,
      refreshUser,
    ],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}

export { BiometricLoginLockedError };
