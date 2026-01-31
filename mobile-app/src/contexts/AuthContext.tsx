import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';
import { authApi } from '@api/auth.api';
import {
    User,
    LoginCredentials,
    AuthState,
} from '@types/auth.types';
import type { UserRole } from '@constants/roles';
import {
    saveToken,
    getToken,
    saveUser,
    getUser,
    clearAuthData,
    saveRememberMe,
    getRememberMe,
} from '@utils/storage';
import { normalizeRole } from '@utils/roleUtils';

function normalizeUserRole(u: any): User {
    if (!u) return u;
    return { ...u, role: normalizeRole(u.role) as UserRole };
}

interface AuthContextType extends AuthState {
    login: (credentials: LoginCredentials) => Promise<void>;
    logout: () => Promise<void>;
    checkAuth: () => Promise<void>;
}

const AuthContext = createContext<AuthContextContextType | undefined>(undefined);

interface AuthProviderProps {
    children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
    const [state, setState] = useState<AuthState>({
        isAuthenticated: false,
        user: null,
        token: null,
        loading: true,
        error: null,
    });

    // Check authentication status on mount
    useEffect(() => {
        checkAuth();
    }, []);

    const checkAuth = async () => {
        try {
            const token = await getToken();
            const user = await getUser();
            const remember = await getRememberMe();

            if (token && user && remember) {
                // Verify token is still valid
                try {
                    const response = await authApi.getProfile();
                    if (response.success && response.data) {
                        const user = normalizeUserRole(response.data);
                        setState({
                            isAuthenticated: true,
                            user,
                            token,
                            loading: false,
                            error: null,
                        });
                        return;
                    }
                } catch (error) {
                    // Token invalid, clear data
                    await clearAuthData();
                }
            }

            setState({
                isAuthenticated: false,
                user: null,
                token: null,
                loading: false,
                error: null,
            });
        } catch (error) {
            setState({
                isAuthenticated: false,
                user: null,
                token: null,
                loading: false,
                error: 'Failed to check authentication',
            });
        }
    };

    const login = async (credentials: LoginCredentials) => {
        try {
            setState((prev) => ({ ...prev, loading: true, error: null }));

            const response = await authApi.login(credentials);

            if (response.success && response.data) {
                const { token, user: rawUser } = response.data;
                const user = normalizeUserRole(rawUser);

                // Save to storage
                await saveToken(token);
                await saveUser(user);
                await saveRememberMe(credentials.remember || false);

                setState({
                    isAuthenticated: true,
                    user,
                    token,
                    loading: false,
                    error: null,
                });
            } else {
                throw new Error(response.message || 'Login failed');
            }
        } catch (error: any) {
            setState((prev) => ({
                ...prev,
                loading: false,
                error: error.message || 'Login failed. Please try again.',
            }));
            throw error;
        }
    };

    const logout = async () => {
        try {
            setState((prev) => ({ ...prev, loading: true }));

            // Call logout API
            await authApi.logout();
        } catch (error) {
            console.error('Logout API error:', error);
        } finally {
            // Clear local data regardless of API result
            await clearAuthData();

            setState({
                isAuthenticated: false,
                user: null,
                token: null,
                loading: false,
                error: null,
            });
        }
    };

    const value: AuthContextType = {
        ...state,
        login,
        logout,
        checkAuth,
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = (): AuthContextType => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }
    return context;
};
