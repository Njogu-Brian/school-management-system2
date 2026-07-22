import type { LoginCredentials, User } from '../../types';

/** Authentication methods supported by the strategy registry. */
export type AuthMethod = 'password' | 'google' | 'biometric' | 'otp';

/** Normalized outcome from any auth provider before session establishment. */
export interface AuthProviderResult {
  method: AuthMethod;
  token: string;
  user: User;
  expiresAt: string | null;
  rememberMe?: boolean;
}

/** Strategy contract — additional providers (OTP, SSO) plug in here later. */
export interface IAuthProvider {
  readonly method: AuthMethod;
  /** Whether this method can be offered on the current device/config. */
  isAvailable(): Promise<boolean>;
  authenticate(input: unknown): Promise<AuthProviderResult>;
}

export type PasswordAuthInput = LoginCredentials;

export type GoogleAuthInput = {
  idToken: string;
};

export type BiometricAuthInput = Record<string, never>;
