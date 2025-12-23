import AsyncStorage from '@react-native-async-storage/async-storage';
import { User } from '@types/auth.types';

const KEYS = {
    TOKEN: '@school_erp_token',
    USER: '@school_erp_user',
    REMEMBER_ME: '@school_erp_remember_me',
};

// Token management
export const saveToken = async (token: string): Promise<void> => {
    try {
        await AsyncStorage.setItem(KEYS.TOKEN, token);
    } catch (error) {
        console.error('Error saving token:', error);
    }
};

export const getToken = async (): Promise<string | null> => {
    try {
        return await AsyncStorage.getItem(KEYS.TOKEN);
    } catch (error) {
        console.error('Error getting token:', error);
        return null;
    }
};

export const clearToken = async (): Promise<void> => {
    try {
        await AsyncStorage.removeItem(KEYS.TOKEN);
    } catch (error) {
        console.error('Error clearing token:', error);
    }
};

// User management
export const saveUser = async (user: User): Promise<void> => {
    try {
        await AsyncStorage.setItem(KEYS.USER, JSON.stringify(user));
    } catch (error) {
        console.error('Error saving user:', error);
    }
};

export const getUser = async (): Promise<User | null> => {
    try {
        const userString = await AsyncStorage.getItem(KEYS.USER);
        return userString ? JSON.parse(userString) : null;
    } catch (error) {
        console.error('Error getting user:', error);
        return null;
    }
};

export const clearUser = async (): Promise<void> => {
    try {
        await AsyncStorage.removeItem(KEYS.USER);
    } catch (error) {
        console.error('Error clearing user:', error);
    }
};

// Remember me
export const saveRememberMe = async (remember: boolean): Promise<void> => {
    try {
        await AsyncStorage.setItem(KEYS.REMEMBER_ME, JSON.stringify(remember));
    } catch (error) {
        console.error('Error saving remember me:', error);
    }
};

export const getRememberMe = async (): Promise<boolean> => {
    try {
        const remember = await AsyncStorage.getItem(KEYS.REMEMBER_ME);
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
            AsyncStorage.removeItem(KEYS.REMEMBER_ME),
        ]);
    } catch (error) {
        console.error('Error clearing auth data:', error);
    }
};
