import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { User, UserRole } from '../types/auth.types';
import { AdminDashboard } from '@screens/Dashboard/AdminDashboard';
import { StudentsNavigator, AttendanceNavigator, FinanceNavigator } from './ModuleNavigators';
import { useTheme } from '@contexts/ThemeContext';

const Tab = createBottomTabNavigator();

interface RoleBasedNavigatorProps {
    user: User;
}

// Placeholder screens (will be created in later phases)
const PlaceholderScreen = () => null;

export const RoleBasedNavigator: React.FC<RoleBasedNavigatorProps> = ({ user }) => {
    const { isDark, colors } = useTheme();

    // Admin/Super Admin Navigation
    if (user.role === UserRole.SUPER_ADMIN || user.role === UserRole.ADMIN || user.role === UserRole.SECRETARY) {
        return (
            <Tab.Navigator
                screenOptions={{
                    headerShown: false,
                    tabBarActiveTintColor: colors.primary,
                    tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
                    tabBarStyle: {
                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                        borderTopColor: isDark ? colors.borderDark : colors.borderLight,
                    },
                }}
            >
                <Tab.Screen
                    name="Dashboard"
                    component={AdminDashboard}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="dashboard" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Students"
                    component={StudentsNavigator}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="school" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Attendance"
                    component={AttendanceNavigator}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="fact-check" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Finance"
                    component={FinanceNavigator}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="payments" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="More"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                    }}
                />
            </Tab.Navigator>
        );
    }

    // Teacher Navigation
    if (user.role === UserRole.TEACHER || user.role === UserRole.SUPERVISOR) {
        return (
            <Tab.Navigator
                screenOptions={{
                    headerShown: false,
                    tabBarActiveTintColor: colors.primary,
                    tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
                    tabBarStyle: {
                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                        borderTopColor: isDark ? colors.borderDark : colors.borderLight,
                    },
                }}
            >
                <Tab.Screen
                    name="Dashboard"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="dashboard" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Classes"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="class" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Attendance"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="fact-check" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="More"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                    }}
                />
            </Tab.Navigator>
        );
    }

    // Parent Navigation
    if (user.role === UserRole.PARENT || user.role === UserRole.GUARDIAN) {
        return (
            <Tab.Navigator
                screenOptions={{
                    headerShown: false,
                    tabBarActiveTintColor: colors.primary,
                    tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
                    tabBarStyle: {
                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                        borderTopColor: isDark ? colors.borderDark : colors.borderLight,
                    },
                }}
            >
                <Tab.Screen
                    name="Dashboard"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="dashboard" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Children"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="child-care" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Payments"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="payment" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="More"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                    }}
                />
            </Tab.Navigator>
        );
    }

    // Student Navigation
    if (user.role === UserRole.STUDENT) {
        return (
            <Tab.Navigator
                screenOptions={{
                    headerShown: false,
                    tabBarActiveTintColor: colors.primary,
                    tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
                    tabBarStyle: {
                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                        borderTopColor: isDark ? colors.borderDark : colors.borderLight,
                    },
                }}
            >
                <Tab.Screen
                    name="Dashboard"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="dashboard" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Homework"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="assignment" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="Results"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="grade" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="More"
                    component={PlaceholderScreen}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                    }}
                />
            </Tab.Navigator>
        );
    }

    // Default fallback
    return (
        <Tab.Navigator
            screenOptions={{
                headerShown: false,
                tabBarActiveTintColor: colors.primary,
            }}
        >
            <Tab.Screen
                name="Dashboard"
                component={AdminDashboard}
                options={{
                    tabBarIcon: ({ color, size }) => <Icon name="dashboard" size={size} color={color} />,
                }}
            />
        </Tab.Navigator>
    );
};
