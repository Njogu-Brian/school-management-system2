import React from 'react';
import { View, StatusBar } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider } from '@contexts/AuthContext';
import { ThemeProvider } from '@contexts/ThemeContext';
import { AppNavigator } from '@navigation/AppNavigator';

const App = () => {
    return (
        <View style={{ flex: 1, backgroundColor: '#f5f3ff' }}>
            <SafeAreaProvider>
                <ThemeProvider>
                    <AuthProvider>
                        <StatusBar barStyle="dark-content" backgroundColor="#f5f3ff" />
                        <AppNavigator />
                    </AuthProvider>
                </ThemeProvider>
            </SafeAreaProvider>
        </View>
    );
};

export default App;
