import React from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { canViewPayrollRecords, canAccessLeaveManagement } from '@utils/staffHrAccess';
import { UserRole } from '@constants/roles';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface MoreItemProps {
    icon: string;
    title: string;
    onPress: () => void;
}

const MoreItem: React.FC<MoreItemProps> = ({ icon, title, onPress }) => {
    const { isDark, colors } = useTheme();
    return (
        <TouchableOpacity
            style={[
                styles.moreItem,
                {
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                },
            ]}
            onPress={onPress}
            activeOpacity={0.7}
        >
            <Icon name={icon as any} size={24} color={colors.primary} />
            <Text style={[styles.moreItemText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {title}
            </Text>
            <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
        </TouchableOpacity>
    );
};

export const MoreScreen = () => {
    const { user, logout } = useAuth();
    const { isDark, colors } = useTheme();
    const navigation = useNavigation<any>();
    const showPayroll = canViewPayrollRecords(user);
    const showLeave = canAccessLeaveManagement(user);
    const canViewStaffDirectory = user?.role !== UserRole.ACADEMIC_ADMIN;

    const handleLogout = async () => {
        await logout();
    };

    return (
        <SafeAreaView
            style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView contentContainerStyle={styles.content}>
                <View style={styles.section}>
                    <MoreItem
                        icon="assignment"
                        title="Academic Reports"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'AcademicReports' }) : navigation.navigate('AcademicReports');
                        }}
                    />
                    <MoreItem
                        icon="feedback"
                        title="Feedback"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'Feedback' }) : navigation.navigate('Feedback');
                        }}
                    />
                    <MoreItem
                        icon="assignment-turned-in"
                        title="Exams & enter results"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'ExamsList' }) : navigation.navigate('ExamsList');
                        }}
                    />
                    {canViewStaffDirectory ? (
                        <MoreItem
                            icon="people"
                            title="Staff Directory"
                            onPress={() => {
                                const parent = navigation.getParent();
                                parent ? parent.navigate('More', { screen: 'StaffDirectory' }) : navigation.navigate('StaffDirectory');
                            }}
                        />
                    ) : null}
                    {showPayroll ? (
                        <MoreItem
                            icon="payments"
                            title="Payroll records"
                            onPress={() => {
                                const parent = navigation.getParent();
                                parent
                                    ? parent.navigate('More', { screen: 'PayrollRecords' })
                                    : navigation.navigate('PayrollRecords');
                            }}
                        />
                    ) : null}
                    {showLeave ? (
                        <MoreItem
                            icon="event-available"
                            title="Leave"
                            onPress={() => {
                                const parent = navigation.getParent();
                                parent
                                    ? parent.navigate('More', { screen: 'LeaveManagement' })
                                    : navigation.navigate('LeaveManagement');
                            }}
                        />
                    ) : null}
                    <MoreItem
                        icon="directions-bus"
                        title="Transport & Routes"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'RoutesList' }) : navigation.navigate('RoutesList');
                        }}
                    />
                    <MoreItem
                        icon="menu-book"
                        title="Library"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'LibraryBooks' }) : navigation.navigate('LibraryBooks');
                        }}
                    />
                    <MoreItem
                        icon="campaign"
                        title="Announcements"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'Announcements' }) : navigation.navigate('Announcements');
                        }}
                    />
                    <MoreItem
                        icon="notifications"
                        title="Notifications"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'Notifications' }) : navigation.navigate('Notifications');
                        }}
                    />
                    <MoreItem
                        icon="settings"
                        title="Settings"
                        onPress={() => {
                            const parent = navigation.getParent();
                            parent ? parent.navigate('More', { screen: 'Settings' }) : navigation.navigate('Settings');
                        }}
                    />
                </View>

                <View style={styles.section}>
                    <TouchableOpacity
                        style={[
                            styles.logoutButton,
                            { backgroundColor: colors.error + '20', borderColor: colors.error },
                        ]}
                        onPress={handleLogout}
                    >
                        <Icon name="logout" size={24} color={colors.error} />
                        <Text style={[styles.logoutText, { color: colors.error }]}>Logout</Text>
                    </TouchableOpacity>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { padding: SPACING.xl, paddingTop: SPACING.md },
    section: { marginBottom: SPACING.xl },
    moreItem: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        marginBottom: SPACING.sm,
        gap: SPACING.md,
    },
    moreItemText: { flex: 1, fontSize: FONT_SIZES.md },
    logoutButton: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        gap: SPACING.sm,
    },
    logoutText: { fontSize: FONT_SIZES.md, fontWeight: '600' },
});
