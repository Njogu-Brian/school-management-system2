import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { User } from '../types/auth.types';
import { AuthNavigator } from './AuthNavigator';
import { RoleBasedNavigator } from './RoleBasedNavigator';
import { OfflineBanner } from '@components/common/OfflineBanner';
import { useNetworkStatus } from '@hooks/useNetworkStatus';
import { usePushNotifications } from '@hooks/usePushNotifications';
import { COLORS } from '@constants/theme';

const AuthenticatedShell: React.FC<{ user: User }> = ({ user }) => {
    const online = useNetworkStatus();
    usePushNotifications(true);
    return (
        <>
            <OfflineBanner visible={!online} />
            <RoleBasedNavigator user={user} />
        </>
    );
};

const DarkTheme = {
    dark: true,
    colors: {
        primary: COLORS.primary,
        background: COLORS.backgroundDark,
        card: COLORS.surfaceDark,
        text: COLORS.textMainDark,
        border: COLORS.borderDark,
        notification: COLORS.primaryLight,
    },
};

const LightTheme = {
    dark: false,
    colors: {
        primary: COLORS.primary,
        background: COLORS.backgroundLight,
        card: COLORS.surfaceLight,
        text: COLORS.textMainLight,
        border: COLORS.borderLight,
        notification: COLORS.primaryLight,
    },
};

export const AppNavigator = () => {
    const { isAuthenticated, user, loading } = useAuth();
    const { isDark, colors } = useTheme();

    if (loading) {
        return (
            <View
                style={[
                    styles.loadingContainer,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    return (
        <NavigationContainer theme={isDark ? DarkTheme : LightTheme}>
            {isAuthenticated && user ? (
                <AuthenticatedShell user={user} />
            ) : (
                <AuthNavigator />
            )}
        </NavigationContainer>
    );
};

const styles = StyleSheet.create({
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
});
