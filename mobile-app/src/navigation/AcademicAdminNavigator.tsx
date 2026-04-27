import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { createStackNavigator } from '@react-navigation/stack';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '@contexts/ThemeContext';

import { AdminDashboard } from '@screens/Dashboard/AdminDashboard';
import { StudentsListScreen } from '@screens/Students/StudentsListScreen';
import { StudentDetailScreen } from '@screens/Students/StudentDetailScreen';

import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { ExamDetailScreen } from '@screens/Academics/ExamDetailScreen';
import { TimetableScreen } from '@screens/Academics/TimetableScreen';
import { ReportCardScreen } from '@screens/Academics/ReportCardScreen';
import { AssignmentsScreen } from '@screens/Academics/AssignmentsScreen';
import { AssignmentDetailScreen } from '@screens/Academics/AssignmentDetailScreen';
import { LessonPlanDetailScreen } from '@screens/Academics/LessonPlanDetailScreen';
import { LessonPlanReviewQueueScreen } from '@screens/SeniorTeacher/LessonPlanReviewQueueScreen';
import { LessonPlanRejectScreen } from '@screens/SeniorTeacher/LessonPlanRejectScreen';

import { MoreNavigator } from './MoreNavigator';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const AcademicAdminStudentsNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="StudentsList" component={StudentsListScreen} />
            <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
            <Stack.Screen name="ReportCard" component={ReportCardScreen} />
        </Stack.Navigator>
    );
};

const AcademicAdminAcademicsNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="ExamsList" component={ExamsListScreen} />
            <Stack.Screen name="ExamDetail" component={ExamDetailScreen} />
            <Stack.Screen name="Timetable" component={TimetableScreen} />
            <Stack.Screen name="ReportCard" component={ReportCardScreen} />
            <Stack.Screen name="Assignments" component={AssignmentsScreen} />
            <Stack.Screen name="AssignmentDetail" component={AssignmentDetailScreen} />
            <Stack.Screen name="ViewAssignment" component={AssignmentDetailScreen} />
            <Stack.Screen name="LessonPlanReviewQueue" component={LessonPlanReviewQueueScreen} />
            <Stack.Screen name="LessonPlanDetail" component={LessonPlanDetailScreen} />
            <Stack.Screen name="LessonPlanReject" component={LessonPlanRejectScreen} />
        </Stack.Navigator>
    );
};

export const AcademicAdminNavigator = () => {
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
                name="Dashboard"
                component={AdminDashboard}
                options={{
                    tabBarIcon: ({ color, size }) => <Icon name="dashboard" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="Students"
                component={AcademicAdminStudentsNavigator}
                options={{
                    tabBarIcon: ({ color, size }) => <Icon name="school" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="Academics"
                component={AcademicAdminAcademicsNavigator}
                options={{
                    tabBarIcon: ({ color, size }) => <Icon name="menu-book" size={size} color={color} />,
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
};

