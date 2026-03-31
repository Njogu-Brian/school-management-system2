import React, { useMemo, useRef, useState, useEffect } from 'react';
import { NavigationContainer, NavigationState, PartialState, createNavigationContainerRef } from '@react-navigation/native';
import { View, ActivityIndicator, StyleSheet, KeyboardAvoidingView, Platform, InteractionManager } from 'react-native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { User } from '../types/auth.types';
import { AuthNavigator } from './AuthNavigator';
import { RoleBasedNavigator } from './RoleBasedNavigator';
import { OfflineBanner } from '@components/common/OfflineBanner';
import { GlobalAppHeader } from '@components/common/GlobalAppHeader';
import { useNetworkStatus } from '@hooks/useNetworkStatus';
import { usePushNotifications } from '@hooks/usePushNotifications';
import { checkForAppUpdate } from '@services/update.service';
import { COLORS } from '@constants/theme';
import { UserRole } from '@constants/roles';
import { useNotificationPreferences } from '@contexts/NotificationPreferencesContext';

type RootState = NavigationState | PartialState<NavigationState>;

const navigationRef = createNavigationContainerRef();

const HIDE_GLOBAL_HEADER_ROUTES = new Set([
    'Dashboard',
    'Home',
    'Main',
    'FinanceHome',
    'StudentHome',
    'StudentHomeTab',
    'ParentDashboard',
    'ParentHome',
    'ParentHomeTab',
]);

const ROUTE_TITLES: Record<string, string> = {
    Students: 'Students',
    StudentsList: 'Students',
    StudentDetail: 'Student details',
    AddStudent: 'Add student',
    EditStudent: 'Edit student',
    Attendance: 'Attendance',
    MarkAttendance: 'Mark attendance',
    TeacherClock: 'Teacher clock',
    AttendanceRecords: 'Attendance records',
    AttendanceAnalytics: 'Attendance analytics',
    Finance: 'Finance',
    FinanceHome: 'Finance',
    InvoicesList: 'Invoices',
    InvoiceDetail: 'Invoice details',
    Payments: 'Payments',
    PaymentsList: 'Payments',
    PaymentDetail: 'Payment details',
    RecordPayment: 'Record payment',
    StudentStatement: 'Student statement',
    More: 'More',
    MoreMenu: 'More',
    Settings: 'Settings',
    StaffDirectory: 'Staff directory',
    StaffDetail: 'Staff details',
    StaffEdit: 'Edit staff',
    PayrollRecords: 'Payroll records',
    MyProfile: 'My profile',
    MySalary: 'My salary',
    Leave: 'Leave management',
    LeaveManagement: 'Leave management',
    ApplyLeave: 'Apply for leave',
    Classes: 'My classes',
    MyClasses: 'My classes',
    ExamsList: 'Exams',
    ExamDetail: 'Exam details',
    ExamMarksSetup: 'Exam marks setup',
    MarksMatrixSetup: 'Marks matrix setup',
    MarksMatrixEntry: 'Marks matrix entry',
    MarksEntry: 'Marks entry',
    Assignments: 'Assignments',
    CreateAssignment: 'Create assignment',
    AssignmentDetail: 'Assignment details',
    ViewAssignment: 'Assignment details',
    LessonPlans: 'Lesson plans',
    LessonPlanDetail: 'Lesson plan details',
    Diary: 'Diary',
    Timetable: 'Timetable',
    ReportCard: 'Report card',
    Notifications: 'Notifications',
    Announcements: 'Announcements',
    AnnouncementDetail: 'Announcement details',
    RoutesList: 'Transport routes',
    RouteDetail: 'Route details',
    Transport: 'Transport routes',
    LibraryBooks: 'Library',
    SupervisedClassrooms: 'Supervised classrooms',
    SupervisedStaff: 'Supervised staff',
    FeeBalances: 'Fee balances',
    PaymentsHub: 'Payments',
    ParentHomeTab: 'Home',
    ParentChildrenTab: 'My children',
    ParentPaymentsTab: 'Fees',
    ParentMoreTab: 'More',
    StudentMoreTab: 'More',
    StudentHomeworkTab: 'Homework',
    StudentResultsTab: 'Results',
    TransactionDetail: 'Transaction details',
};

function toTitleCaseFromRoute(routeName: string): string {
    if (ROUTE_TITLES[routeName]) return ROUTE_TITLES[routeName];
    return routeName
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/[_-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (m) => m.toUpperCase());
}

function getCurrentRouteName(state?: RootState): string | null {
    if (!state || typeof state.index !== 'number' || !state.routes?.length) return null;
    const route = state.routes[state.index] as any;
    if (route.state) return getCurrentRouteName(route.state as RootState);
    return route.name ?? null;
}

function getDashboardFallback(user: User) {
    if (
        user.role === UserRole.SUPER_ADMIN ||
        user.role === UserRole.ADMIN ||
        user.role === UserRole.SECRETARY ||
        user.role === UserRole.ACCOUNTANT ||
        user.role === UserRole.FINANCE
    ) {
        return { name: 'Dashboard' };
    }
    if (
        user.role === UserRole.TEACHER ||
        user.role === UserRole.SENIOR_TEACHER ||
        user.role === UserRole.SUPERVISOR
    ) {
        return { name: 'Main', params: { screen: 'Home' } };
    }
    if (user.role === UserRole.PARENT || user.role === UserRole.GUARDIAN) {
        return { name: 'ParentHomeTab' };
    }
    if (user.role === UserRole.STUDENT) {
        return { name: 'StudentHomeTab' };
    }
    return { name: 'Dashboard' };
}

const AuthenticatedShell: React.FC<{ user: User; currentRouteName: string | null }> = ({ user, currentRouteName }) => {
    const online = useNetworkStatus();
    const { preferences } = useNotificationPreferences();
    usePushNotifications(preferences.push_enabled);
    const checkedOnce = useRef(false);
    const shouldShowGlobalHeader = currentRouteName ? !HIDE_GLOBAL_HEADER_ROUTES.has(currentRouteName) : false;

    useEffect(() => {
        if (checkedOnce.current) return;
        checkedOnce.current = true;
        const task = InteractionManager.runAfterInteractions(() => {
            checkForAppUpdate({ silent: false, showNoUpdateMessage: false });
        });
        return () => task.cancel();
    }, []);

    const handleBack = () => {
        if (!navigationRef.isReady()) return;
        if (navigationRef.canGoBack()) {
            navigationRef.goBack();
            return;
        }

        const fallback = getDashboardFallback(user);
        navigationRef.navigate(fallback.name as never, fallback.params as never);
    };

    const handleSettings = () => {
        if (!navigationRef.isReady()) return;
        if (
            user.role === UserRole.SUPER_ADMIN ||
            user.role === UserRole.ADMIN ||
            user.role === UserRole.SECRETARY ||
            user.role === UserRole.ACCOUNTANT ||
            user.role === UserRole.FINANCE
        ) {
            navigationRef.navigate('More' as never, { screen: 'Settings' } as never);
            return;
        }
        navigationRef.navigate('Settings' as never);
    };

    const globalTitle = useMemo(
        () => (currentRouteName ? toTitleCaseFromRoute(currentRouteName) : 'Home'),
        [currentRouteName]
    );
    const canOpenSettings =
        user.role === UserRole.SUPER_ADMIN ||
        user.role === UserRole.ADMIN ||
        user.role === UserRole.SECRETARY ||
        user.role === UserRole.ACCOUNTANT ||
        user.role === UserRole.FINANCE ||
        user.role === UserRole.TEACHER ||
        user.role === UserRole.SENIOR_TEACHER ||
        user.role === UserRole.SUPERVISOR;

    return (
        <KeyboardAvoidingView
            style={styles.flex}
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
            keyboardVerticalOffset={0}
        >
            {shouldShowGlobalHeader ? (
                <GlobalAppHeader
                    title={globalTitle}
                    onBack={handleBack}
                    onSettings={handleSettings}
                    showSettings={canOpenSettings}
                />
            ) : null}
            <OfflineBanner visible={!online} />
            <RoleBasedNavigator user={user} />
        </KeyboardAvoidingView>
    );
};

const DarkTheme = {
    dark: true,
    colors: {
        primary: COLORS.primary,
        background: COLORS.backgroundDark,
        card: COLORS.surfaceDark,
        text: COLORS.textMainDark,
        border: COLORS.borderDark,
        notification: COLORS.primaryLight,
    },
};

const LightTheme = {
    dark: false,
    colors: {
        primary: COLORS.primary,
        background: COLORS.backgroundLight,
        card: COLORS.surfaceLight,
        text: COLORS.textMainLight,
        border: COLORS.borderLight,
        notification: COLORS.primaryLight,
    },
};

export const AppNavigator = () => {
    const { isAuthenticated, user, loading } = useAuth();
    const { isDark, colors } = useTheme();
    const [currentRouteName, setCurrentRouteName] = useState<string | null>(null);

    if (loading) {
        return (
            <View
                style={[
                    styles.loadingContainer,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    return (
        <NavigationContainer
            ref={navigationRef}
            theme={isDark ? DarkTheme : LightTheme}
            onReady={() => setCurrentRouteName(getCurrentRouteName(navigationRef.getRootState() as RootState))}
            onStateChange={() => setCurrentRouteName(getCurrentRouteName(navigationRef.getRootState() as RootState))}
        >
            {isAuthenticated && user ? (
                <AuthenticatedShell user={user} currentRouteName={currentRouteName} />
            ) : (
                <AuthNavigator />
            )}
        </NavigationContainer>
    );
};

const styles = StyleSheet.create({
    flex: {
        flex: 1,
    },
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
});
