import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { StudentHomeScreen } from '@screens/Student/StudentHomeScreen';
import { StudentHomeworkScreen } from '@screens/Student/StudentHomeworkScreen';
import { StudentResultsScreen } from '@screens/Student/StudentResultsScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { MoreScreen } from '@screens/More/MoreScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const StudentHomeStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="StudentHome" component={StudentHomeScreen} />
    </Stack.Navigator>
);

const StudentHomeworkStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="StudentHomeworkMain" component={StudentHomeworkScreen} />
    </Stack.Navigator>
);

const StudentResultsStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="StudentResultsMain" component={StudentResultsScreen} />
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
                component={StudentHomeworkStack}
                options={{
                    tabBarLabel: 'Homework',
                    tabBarIcon: ({ color, size }) => <Icon name="assignment" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="StudentResultsTab"
                component={StudentResultsStack}
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
