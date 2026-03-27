import AsyncStorage from '@react-native-async-storage/async-storage';
import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';

const BIOMETRIC_ENABLED_KEY = '@school_erp_biometric_enabled';
const BIOMETRIC_CREDENTIALS_KEY = 'school_erp_biometric_credentials';

type BiometricCredentials = {
    identifier: string;
    password: string;
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
        await SecureStore.deleteItemAsync(BIOMETRIC_CREDENTIALS_KEY);
    }
}

export async function getBiometricEnabled(): Promise<boolean> {
    const raw = await AsyncStorage.getItem(BIOMETRIC_ENABLED_KEY);
    return raw ? JSON.parse(raw) : false;
}

export async function saveBiometricCredentials(identifier: string, password: string): Promise<void> {
    const payload: BiometricCredentials = { identifier, password };
    await SecureStore.setItemAsync(BIOMETRIC_CREDENTIALS_KEY, JSON.stringify(payload), {
        requireAuthentication: false,
    });
}

export async function getBiometricCredentials(): Promise<BiometricCredentials | null> {
    const raw = await SecureStore.getItemAsync(BIOMETRIC_CREDENTIALS_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw) as BiometricCredentials;
    } catch {
        return null;
    }
}
