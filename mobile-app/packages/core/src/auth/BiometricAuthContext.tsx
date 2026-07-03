import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import {
  canUseBiometrics,
  getBiometricEnabled,
  getBiometricTypeLabel,
  hasBiometricUnlockAvailable,
  isBiometricLoginLocked,
} from '../storage/biometricStorage';
import { useAuth } from './AuthContext';

export interface BiometricAuthContextValue {
  /** Device supports Face ID / fingerprint / other enrolled biometrics. */
  deviceSupportsBiometrics: boolean;
  /** User has enabled biometrics and a saved session bundle exists. */
  unlockAvailable: boolean;
  /** Biometric unlock locked after too many failures. */
  isLocked: boolean;
  /** e.g. "Face ID" or "Fingerprint". */
  typeLabel: string;
  enabled: boolean;
  refresh: () => Promise<void>;
  unlock: () => Promise<void>;
  submitting: boolean;
  error: string | null;
}

const BiometricAuthContext = createContext<BiometricAuthContextValue | undefined>(undefined);

/**
 * Biometric unlock provider. Biometrics only rehydrate an existing Sanctum session —
 * the user must sign in with email and password at least once before unlock is offered.
 */
export const BiometricAuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { unlockWithBiometrics, submitting, error } = useAuth();
  const [deviceSupportsBiometrics, setDeviceSupports] = useState(false);
  const [unlockAvailable, setUnlockAvailable] = useState(false);
  const [isLocked, setIsLocked] = useState(false);
  const [typeLabel, setTypeLabel] = useState('Biometrics');
  const [enabled, setEnabled] = useState(false);

  const refresh = useCallback(async () => {
    const [deviceOk, unlockOk, locked, pref, label] = await Promise.all([
      canUseBiometrics(),
      hasBiometricUnlockAvailable(),
      isBiometricLoginLocked(),
      getBiometricEnabled(),
      getBiometricTypeLabel(),
    ]);
    setDeviceSupports(deviceOk);
    setUnlockAvailable(unlockOk);
    setIsLocked(locked);
    setEnabled(pref);
    setTypeLabel(label);
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const unlock = useCallback(async () => {
    await unlockWithBiometrics();
    await refresh();
  }, [unlockWithBiometrics, refresh]);

  const value = useMemo<BiometricAuthContextValue>(
    () => ({
      deviceSupportsBiometrics,
      unlockAvailable,
      isLocked,
      typeLabel,
      enabled,
      refresh,
      unlock,
      submitting,
      error,
    }),
    [
      deviceSupportsBiometrics,
      unlockAvailable,
      isLocked,
      typeLabel,
      enabled,
      refresh,
      unlock,
      submitting,
      error,
    ],
  );

  return (
    <BiometricAuthContext.Provider value={value}>{children}</BiometricAuthContext.Provider>
  );
};

export function useBiometricAuth(): BiometricAuthContextValue {
  const ctx = useContext(BiometricAuthContext);
  if (!ctx) {
    throw new Error('useBiometricAuth must be used within a BiometricAuthProvider');
  }
  return ctx;
}
