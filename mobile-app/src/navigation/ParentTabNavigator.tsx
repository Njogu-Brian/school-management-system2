import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { ParentDashboardScreen } from '@screens/Parent/ParentDashboardScreen';
import { ParentPaymentsScreen } from '@screens/Parent/ParentPaymentsScreen';
import { StudentsListScreen } from '@screens/Students/StudentsListScreen';
import { StudentDetailScreen } from '@screens/Students/StudentDetailScreen';
import { StudentStatementScreen } from '@screens/Finance/StudentStatementScreen';
import { MoreScreen } from '@screens/More/MoreScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const ParentHomeStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="ParentHome" component={ParentDashboardScreen} />
    </Stack.Navigator>
);

const ParentChildrenStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen
            name="ChildrenList"
            component={StudentsListScreen}
            initialParams={{ title: 'My children' }}
        />
        <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
        <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    </Stack.Navigator>
);

const ParentPaymentsStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="ParentPaymentsMain" component={ParentPaymentsScreen} />
        <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
        <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    </Stack.Navigator>
);

const ParentMoreStack = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="MoreMenu" component={MoreScreen} />
        <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
        <Stack.Screen name="Notifications" component={NotificationsScreen} />
    </Stack.Navigator>
);

export const ParentTabNavigator: React.FC = () => {
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
                name="ParentHomeTab"
                component={ParentHomeStack}
                options={{
                    tabBarLabel: 'Home',
                    tabBarIcon: ({ color, size }) => <Icon name="home" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="ParentChildrenTab"
                component={ParentChildrenStack}
                options={{
                    tabBarLabel: 'Children',
                    tabBarIcon: ({ color, size }) => <Icon name="child-care" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="ParentPaymentsTab"
                component={ParentPaymentsStack}
                options={{
                    tabBarLabel: 'Fees',
                    tabBarIcon: ({ color, size }) => <Icon name="payment" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="ParentMoreTab"
                component={ParentMoreStack}
                options={{
                    tabBarLabel: 'More',
                    tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                }}
            />
        </Tab.Navigator>
    );
};
