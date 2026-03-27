import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { StudentHomeScreen } from '@screens/Student/StudentHomeScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { MoreScreen } from '@screens/More/MoreScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const Placeholder: React.FC<{ title: string }> = ({ title }) => {
    const { isDark, colors } = useTheme();
    return (
        <View style={[styles.ph, { backgroundColor: isDark ? colors.backgroundDark : '#f8fafc' }]}>
            <Text style={{ color: isDark ? colors.textMainDark : '#0f172a', fontSize: 16 }}>{title}</Text>
            <Text style={{ color: isDark ? colors.textSubDark : '#64748b', marginTop: 8 }}>Coming soon</Text>
        </View>
    );
};

const StudentHomeStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="StudentHome" component={StudentHomeScreen} />
    </Stack.Navigator>
);

const StudentMoreStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="MoreMenu" component={MoreScreen} />
        <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
        <Stack.Screen name="Notifications" component={NotificationsScreen} />
    </Stack.Navigator>
);

export const StudentTabNavigator: React.FC = () => {
    const { isDark, colors } = useTheme();
    const insets = useSafeAreaInsets();

    return (
        <Tab.Navigator
            screenOptions={{
                headerShown: false,
                tabBarActiveTintColor: colors.primary,
                tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
                tabBarStyle: {
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderTopColor: isDark ? colors.borderDark : colors.borderLight,
                    height: 56 + insets.bottom,
                    paddingTop: 6,
                    paddingBottom: Math.max(insets.bottom, 6),
                },
                tabBarItemStyle: { paddingVertical: 0 },
            }}
        >
            <Tab.Screen
                name="StudentHomeTab"
                component={StudentHomeStack}
                options={{
                    tabBarLabel: 'Home',
                    tabBarIcon: ({ color, size }) => <Icon name="home" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="StudentHomeworkTab"
                children={() => <Placeholder title="Homework" />}
                options={{
                    tabBarLabel: 'Homework',
                    tabBarIcon: ({ color, size }) => <Icon name="assignment" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="StudentResultsTab"
                children={() => <Placeholder title="Results" />}
                options={{
                    tabBarLabel: 'Results',
                    tabBarIcon: ({ color, size }) => <Icon name="grade" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="StudentMoreTab"
                component={StudentMoreStack}
                options={{
                    tabBarLabel: 'More',
                    tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                }}
            />
        </Tab.Navigator>
    );
};

const styles = StyleSheet.create({
    ph: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24 },
});
