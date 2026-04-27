import React from 'react';
import { View } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider, useAuth } from '@contexts/AuthContext';
import { ThemeProvider, useTheme } from '@contexts/ThemeContext';
import { NotificationPreferencesProvider } from '@contexts/NotificationPreferencesContext';
import { AppNavigator } from '@navigation/AppNavigator';
import { AppErrorBoundary } from '@components/common/AppErrorBoundary';

/** Status bar: login hero is dark (light icons); main app uses theme. */
const ThemedRoot: React.FC = () => {
    const { isDark, colors } = useTheme();
    const { isAuthenticated } = useAuth();
    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;
    const statusStyle =
        !isAuthenticated ? 'light' : (isDark ? 'light' : 'dark') as 'light' | 'dark';

    return (
        <View style={{ flex: 1, backgroundColor: bg }}>
            <StatusBar style={statusStyle} />
            <NotificationPreferencesProvider>
                <AppNavigator />
            </NotificationPreferencesProvider>
        </View>
    );
};

const App = () => {
    return (
        <ThemeProvider>
            <SafeAreaProvider>
                <AppErrorBoundary>
                    <AuthProvider>
                        <ThemedRoot />
                    </AuthProvider>
                </AppErrorBoundary>
            </SafeAreaProvider>
        </ThemeProvider>
    );
};

export default App;
