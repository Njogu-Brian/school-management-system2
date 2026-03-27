import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { User } from '../types/auth.types';
import { UserRole } from '@constants/roles';
import { AdminDashboard } from '@screens/Dashboard/AdminDashboard';
import { StudentsNavigator, AttendanceNavigator, FinanceNavigator, PaymentsNavigator } from './ModuleNavigators';
import { MoreNavigator } from './MoreNavigator';
import { TeacherNavigator } from './TeacherNavigator';
import { ParentTabNavigator } from './ParentTabNavigator';
import { StudentTabNavigator } from './StudentTabNavigator';
import { useTheme } from '@contexts/ThemeContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

const Tab = createBottomTabNavigator();

interface RoleBasedNavigatorProps {
    user: User;
}

export const RoleBasedNavigator: React.FC<RoleBasedNavigatorProps> = ({ user }) => {
    const { isDark, colors } = useTheme();
    const insets = useSafeAreaInsets();

    // Admin/Super Admin Navigation
    if (
        user.role === UserRole.SUPER_ADMIN ||
        user.role === UserRole.ADMIN ||
        user.role === UserRole.SECRETARY ||
        user.role === UserRole.ACCOUNTANT ||
        user.role === UserRole.FINANCE
    ) {
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
                    name="Payments"
                    component={PaymentsNavigator}
                    options={{
                        tabBarIcon: ({ color, size }) => (
                            <Icon name="account-balance-wallet" size={size} color={color} />
                        ),
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
                        tabBarIcon: ({ color, size }) => <Icon name="receipt-long" size={size} color={color} />,
                    }}
                />
                <Tab.Screen
                    name="More"
                    component={MoreNavigator}
                    options={{
                        tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                    }}
                />
            </Tab.Navigator>
        );
    }

    // Teacher & Senior Teacher Navigation – full stack: Dashboard, My Classes, Attendance, Academics, Transport, Diary, Profile, Salary; senior teachers get extra (Supervised Classrooms/Staff, Fee Balances)
    if (user.role === UserRole.TEACHER || user.role === UserRole.SENIOR_TEACHER || user.role === UserRole.SUPERVISOR) {
        return <TeacherNavigator />;
    }

    // Parent / guardian
    if (user.role === UserRole.PARENT || user.role === UserRole.GUARDIAN) {
        return <ParentTabNavigator />;
    }

    // Student
    if (user.role === UserRole.STUDENT) {
        return <StudentTabNavigator />;
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
