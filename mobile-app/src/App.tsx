import React from 'react';
import { View, StatusBar } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider } from '@contexts/AuthContext';
import { ThemeProvider } from '@contexts/ThemeContext';
import { AppNavigator } from '@navigation/AppNavigator';

const App = () => {
    return (
        <View style={{ flex: 1, backgroundColor: '#f8fafc' }}>
            <SafeAreaProvider>
                <ThemeProvider>
                    <AuthProvider>
                        <StatusBar barStyle="dark-content" backgroundColor="#f8fafc" />
                        <AppNavigator />
                    </AuthProvider>
                </ThemeProvider>
            </SafeAreaProvider>
        </View>
    );
};

export default App;
