import React, { createContext, useState, useContext, useEffect, useRef, ReactNode } from 'react';
import { authApi } from '@api/auth.api';
import { apiClient } from '@api/client';
import {
    User,
    LoginCredentials,
    AuthState,
} from 'types/auth.types';
import type { UserRole } from '@constants/roles';
import {
    saveToken,
    getToken,
    saveUser,
    getUser,
    clearAuthData,
    saveRememberMe,
} from '@utils/storage';
import { normalizeRole } from '@utils/roleUtils';

function normalizeUserRole(u: any): User {
    if (!u) return u;
    return { ...u, role: normalizeRole(u.role) as UserRole };
}

interface AuthContextType extends AuthState {
    login: (credentials: LoginCredentials) => Promise<void>;
    completeLogin: (payload: { token: string; user: User }) => Promise<void>;
    logout: () => Promise<void>;
    checkAuth: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

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

            // Restore session whenever a token exists — validate with the API (not gated on "remember me").
            // Previously we only called getProfile when remember===true, which left tokens in storage but
            // set isAuthenticated false after restart, or caused confusing 401s when the UI thought the user was logged in.
            if (token) {
                try {
                    const response = await authApi.getProfile();
                    if (response.success && response.data) {
                        const user = normalizeUserRole(response.data);
                        await saveUser(user);
                        setState({
                            isAuthenticated: true,
                            user,
                            token,
                            loading: false,
                            error: null,
                        });
                        return;
                    }
                } catch (err: any) {
                    // Only clear the stored session on a true 401. Network errors (no
                    // response, offline, 5xx) must not sign the user out — we optimistically
                    // restore from cached user until the next successful call.
                    const status = err?.status;
                    if (status === 401) {
                        await clearAuthData();
                    } else {
                        const cached = await getUser();
                        if (cached) {
                            setState({
                                isAuthenticated: true,
                                user: normalizeUserRole(cached),
                                token,
                                loading: false,
                                error: null,
                            });
                            return;
                        }
                    }
                }
            }

            setState({
                isAuthenticated: false,
                user: null,
                token: null,
                loading: false,
                error: null,
            });
        } catch {
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
        } catch (err) {
            const msg =
                (err as { message?: string })?.message ||
                (err instanceof Error ? err.message : null) ||
                String(err) ||
                'Login failed. Please try again.';
            setState((prev) => ({
                ...prev,
                loading: false,
                error: msg,
            }));
            throw err;
        }
    };

    const completeLogin = async (payload: { token: string; user: User }) => {
        const user = normalizeUserRole(payload.user);
        await saveToken(payload.token);
        await saveUser(user);
        await saveRememberMe(true);

        setState({
            isAuthenticated: true,
            user,
            token: payload.token,
            loading: false,
            error: null,
        });
    };

    const logoutRef = useRef<(() => Promise<void>) | undefined>(undefined);

    const logout = async () => {
        try {
            setState((prev) => ({ ...prev, loading: true }));

            const token = await getToken();
            if (token) {
                try {
                    await authApi.logout();
                } catch {
                    /* token may already be invalid; local clear still runs */
                }
            }
        } catch (err) {
            console.error('Logout error:', err);
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

    logoutRef.current = logout;

    useEffect(() => {
        apiClient.setOnUnauthorized(() => {
            logoutRef.current?.();
        });
        return () => apiClient.setOnUnauthorized(null);
    }, []);

    const value: AuthContextType = {
        ...state,
        login,
        completeLogin,
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
