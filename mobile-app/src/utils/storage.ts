import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { User } from '@types/auth.types';

const ASYNC_KEYS = {
    TOKEN: '@school_erp_token',
    USER: '@school_erp_user',
    REMEMBER_ME: '@school_erp_remember_me',
};

const SECURE_KEYS = {
    TOKEN: 'school_erp_token',
};

const SECURE_OPTIONS: SecureStore.SecureStoreOptions = {
    keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
};

// Token management
export const saveToken = async (token: string): Promise<void> => {
    try {
        await SecureStore.setItemAsync(SECURE_KEYS.TOKEN, token, SECURE_OPTIONS);
        // Clean up any legacy token left in AsyncStorage from old builds.
        await AsyncStorage.removeItem(ASYNC_KEYS.TOKEN);
    } catch (error) {
        console.error('Error saving token:', error);
    }
};

export const getToken = async (): Promise<string | null> => {
    try {
        const secureToken = await SecureStore.getItemAsync(SECURE_KEYS.TOKEN, SECURE_OPTIONS);
        if (secureToken) {
            return secureToken;
        }

        // One-time migration path for users upgrading from older app versions.
        const legacyToken = await AsyncStorage.getItem(ASYNC_KEYS.TOKEN);
        if (!legacyToken) {
            return null;
        }

        await SecureStore.setItemAsync(SECURE_KEYS.TOKEN, legacyToken, SECURE_OPTIONS);
        await AsyncStorage.removeItem(ASYNC_KEYS.TOKEN);
        return legacyToken;
    } catch (error) {
        console.error('Error getting token:', error);
        return null;
    }
};

export const clearToken = async (): Promise<void> => {
    try {
        await Promise.all([
            SecureStore.deleteItemAsync(SECURE_KEYS.TOKEN, SECURE_OPTIONS),
            AsyncStorage.removeItem(ASYNC_KEYS.TOKEN),
        ]);
    } catch (error) {
        console.error('Error clearing token:', error);
    }
};

// User management
export const saveUser = async (user: User): Promise<void> => {
    try {
        await AsyncStorage.setItem(ASYNC_KEYS.USER, JSON.stringify(user));
    } catch (error) {
        console.error('Error saving user:', error);
    }
};

export const getUser = async (): Promise<User | null> => {
    try {
        const userString = await AsyncStorage.getItem(ASYNC_KEYS.USER);
        return userString ? JSON.parse(userString) : null;
    } catch (error) {
        console.error('Error getting user:', error);
        return null;
    }
};

export const clearUser = async (): Promise<void> => {
    try {
        await AsyncStorage.removeItem(ASYNC_KEYS.USER);
    } catch (error) {
        console.error('Error clearing user:', error);
    }
};

// Remember me
export const saveRememberMe = async (remember: boolean): Promise<void> => {
    try {
        await AsyncStorage.setItem(ASYNC_KEYS.REMEMBER_ME, JSON.stringify(remember));
    } catch (error) {
        console.error('Error saving remember me:', error);
    }
};

export const getRememberMe = async (): Promise<boolean> => {
    try {
        const remember = await AsyncStorage.getItem(ASYNC_KEYS.REMEMBER_ME);
        return remember ? JSON.parse(remember) : false;
    } catch (error) {
        console.error('Error getting remember me:', error);
        return false;
    }
};

// Clear all auth data
export const clearAuthData = async (): Promise<void> => {
    try {
        await Promise.all([
            clearToken(),
            clearUser(),
            AsyncStorage.removeItem(ASYNC_KEYS.REMEMBER_ME),
        ]);
    } catch (error) {
        console.error('Error clearing auth data:', error);
    }
};
