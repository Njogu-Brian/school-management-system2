// @ts-nocheck
import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { DriverHomeScreen } from '@screens/Transport/DriverHomeScreen';
import { DriverActiveTripScreen } from '@screens/Transport/DriverActiveTripScreen';
import { RoutesListScreen } from '@screens/Transport/RoutesListScreen';
import { RouteDetailScreen } from '@screens/Transport/RouteDetailScreen';
import { SettingsScreen } from '@screens/Settings/SettingsScreen';

const Tab = createBottomTabNavigator();
const HomeStack = createStackNavigator();
const RoutesStack = createStackNavigator();
const AccountStack = createStackNavigator();

const DriverHomeStack = () => (
    <HomeStack.Navigator screenOptions={{ headerShown: false }}>
        <HomeStack.Screen name="DriverHomeMain" component={DriverHomeScreen} />
        <HomeStack.Screen name="ActiveTrip" component={DriverActiveTripScreen} />
        <HomeStack.Screen name="RouteDetail" component={RouteDetailScreen} />
    </HomeStack.Navigator>
);

const DriverRoutesStack = () => (
    <RoutesStack.Navigator screenOptions={{ headerShown: false }}>
        <RoutesStack.Screen name="RoutesList" component={RoutesListScreen} />
        <RoutesStack.Screen name="RouteDetail" component={RouteDetailScreen} />
    </RoutesStack.Navigator>
);

const DriverAccountStack = () => (
    <AccountStack.Navigator screenOptions={{ headerShown: false }}>
        <AccountStack.Screen name="DriverSettings" component={SettingsScreen} />
    </AccountStack.Navigator>
);

/** Stitch driver shell: Home | Routes | Account */
export const DriverTabNavigator: React.FC = () => {
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
                name="DriverHomeTab"
                component={DriverHomeStack}
                options={{
                    tabBarLabel: 'Home',
                    tabBarIcon: ({ color, size }) => <Icon name="home" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="DriverRoutesTab"
                component={DriverRoutesStack}
                options={{
                    tabBarLabel: 'Routes',
                    tabBarIcon: ({ color, size }) => <Icon name="alt-route" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="DriverAccountTab"
                component={DriverAccountStack}
                options={{
                    tabBarLabel: 'Account',
                    tabBarIcon: ({ color, size }) => <Icon name="person" size={size} color={color} />,
                }}
            />
        </Tab.Navigator>
    );
};
