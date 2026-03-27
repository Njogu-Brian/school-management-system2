import React from 'react';
import { View, StatusBar } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider } from '@contexts/AuthContext';
import { ThemeProvider } from '@contexts/ThemeContext';
import { NotificationPreferencesProvider } from '@contexts/NotificationPreferencesContext';
import { AppNavigator } from '@navigation/AppNavigator';

const App = () => {
    return (
        <View style={{ flex: 1, backgroundColor: '#f5f3ff' }}>
            <SafeAreaProvider>
                <ThemeProvider>
                    <AuthProvider>
                        <NotificationPreferencesProvider>
                            <StatusBar barStyle="light-content" backgroundColor="#3B0056" translucent={false} />
                            <AppNavigator />
                        </NotificationPreferencesProvider>
                    </AuthProvider>
                </ThemeProvider>
            </SafeAreaProvider>
        </View>
    );
};

export default App;
