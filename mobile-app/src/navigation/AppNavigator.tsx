import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { AuthNavigator } from './AuthNavigator';
import { RoleBasedNavigator } from './RoleBasedNavigator';

const Stack = createStackNavigator();

const DarkTheme = {
    dark: true,
    colors: {
        primary: '#6366f1',
        background: '#0f172a',
        card: '#1e293b',
        text: '#f8fafc',
        border: '#334155',
        notification: '#6366f1',
    },
};

const LightTheme = {
    dark: false,
    colors: {
        primary: '#6366f1',
        background: '#f8fafc',
        card: '#ffffff',
        text: '#0f172a',
        border: '#e2e8f0',
        notification: '#6366f1',
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
                <RoleBasedNavigator user={user} />
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
