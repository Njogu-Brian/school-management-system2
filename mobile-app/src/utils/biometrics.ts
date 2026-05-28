import AsyncStorage from '@react-native-async-storage/async-storage';
import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';

const BIOMETRIC_ENABLED_KEY = '@school_erp_biometric_enabled';
const BIOMETRIC_AUTH_BUNDLE_KEY = 'school_erp_biometric_auth_bundle';
const BIOMETRIC_FAILURE_COUNT_KEY = '@school_erp_biometric_failure_count';

export const BIOMETRIC_MAX_FAILURES = 5;

type BiometricAuthBundle = {
    token: string;
};

export async function canUseBiometrics(): Promise<boolean> {
    const hasHardware = await LocalAuthentication.hasHardwareAsync();
    const enrolled = await LocalAuthentication.isEnrolledAsync();
    return hasHardware && enrolled;
}

export async function authenticateWithBiometrics(reason = 'Authenticate to login'): Promise<boolean> {
    const result = await LocalAuthentication.authenticateAsync({
        promptMessage: reason,
        fallbackLabel: 'Use device passcode',
        disableDeviceFallback: false,
    });
    return result.success;
}

export async function setBiometricEnabled(enabled: boolean): Promise<void> {
    await AsyncStorage.setItem(BIOMETRIC_ENABLED_KEY, JSON.stringify(enabled));
    if (!enabled) {
        await SecureStore.deleteItemAsync(BIOMETRIC_AUTH_BUNDLE_KEY);
        await clearBiometricFailureCount();
    }
}

export async function getBiometricEnabled(): Promise<boolean> {
    const raw = await AsyncStorage.getItem(BIOMETRIC_ENABLED_KEY);
    return raw ? JSON.parse(raw) : false;
}

export async function saveBiometricAuthBundle(token: string): Promise<void> {
    const payload: BiometricAuthBundle = { token };
    await SecureStore.setItemAsync(BIOMETRIC_AUTH_BUNDLE_KEY, JSON.stringify(payload), {
        requireAuthentication: true,
    });
    await clearBiometricFailureCount();
}

export async function getBiometricAuthBundle(): Promise<BiometricAuthBundle | null> {
    const raw = await SecureStore.getItemAsync(BIOMETRIC_AUTH_BUNDLE_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw) as BiometricAuthBundle;
    } catch {
        return null;
    }
}

export async function getBiometricFailureCount(): Promise<number> {
    const raw = await AsyncStorage.getItem(BIOMETRIC_FAILURE_COUNT_KEY);
    const n = raw ? parseInt(raw, 10) : 0;
    return Number.isFinite(n) ? n : 0;
}

export async function incrementBiometricFailureCount(): Promise<number> {
    const next = (await getBiometricFailureCount()) + 1;
    await AsyncStorage.setItem(BIOMETRIC_FAILURE_COUNT_KEY, String(next));
    return next;
}

export async function clearBiometricFailureCount(): Promise<void> {
    await AsyncStorage.removeItem(BIOMETRIC_FAILURE_COUNT_KEY);
}

export async function isBiometricLoginLocked(): Promise<boolean> {
    return (await getBiometricFailureCount()) >= BIOMETRIC_MAX_FAILURES;
}
