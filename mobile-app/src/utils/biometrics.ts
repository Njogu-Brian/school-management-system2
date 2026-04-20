import AsyncStorage from '@react-native-async-storage/async-storage';
import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';

const BIOMETRIC_ENABLED_KEY = '@school_erp_biometric_enabled';
const BIOMETRIC_AUTH_BUNDLE_KEY = 'school_erp_biometric_auth_bundle';

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
