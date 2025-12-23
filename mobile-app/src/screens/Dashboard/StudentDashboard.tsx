import React, { useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StudentDashboardProps {
    navigation: any;
}

export const StudentDashboard: React.FC<StudentDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);

    // Mock data
    const [studentInfo] = useState({
        class: 'Form 3A',
        attendance: 92,
        feeBalance: 15000,
        lastExamGrade: 'B+',
        pendingAssignments: 3,
    });

    const [todaySchedule] = useState([
        { time: '08:00 AM', subject: 'Mathematics', room: 'Room 12' },
        { time: '10:00 AM', subject: 'English', room: 'Room 5' },
        { time: '12:00 PM', subject: 'Physics', room: 'Lab 2' },
    ]);

    const [assignments] = useState([
        { id: 1, title: 'Math Assignment', subject: 'Mathematics', dueDate: '2024-01-25', status: 'pending' },
        { id: 2, title: 'Essay Writing', subject: 'English', dueDate: '2024-01-26', status: 'pending' },
    ]);

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 1000);
    };

    const quickActions = [
        { id: 1, title: 'Timetable', icon: 'schedule', screen: 'Timetable', color: '#3b82f6' },
        { id: 2, title: 'Assignments', icon: 'assignment', screen: 'Assignments', color: '#10b981' },
        { id: 3, title: 'Results', icon: 'assessment', screen: 'Results', color: '#f59e0b' },
        { id: 4, title: 'Attendance', icon: 'event-available', screen: 'MyAttendance', color: '#8b5cf6' },
    ];

    const renderQuickAction = (action: any) => (
        <TouchableOpacity
            key={action.id}
            style={[styles.actionCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}
            onPress={() => navigation.navigate(action.screen)}
        >
            <View style={[styles.actionIcon, { backgroundColor: action.color + '20' }]}>
                <Icon name={action.icon} size={24} color={action.color} />
            </View>
            <Text style={[styles.actionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {action.title}
            </Text>
        </TouchableOpacity>
    );

    const renderScheduleItem = (item: any, index: number) => (
        <View
            key={index}
            style={[styles.scheduleItem, { borderBottomColor: isDark ? colors.borderDark : colors.borderLight }]}
        >
            <Text style={[styles.scheduleTime, { color: colors.primary }]}>{item.time}</Text>
            <View style={styles.scheduleInfo}>
                <Text style={[styles.scheduleSubject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {item.subject}
                </Text>
                <Text style={[styles.scheduleRoom, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    {item.room}
                </Text>
            </View>
        </View>
    );

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <View>
                    <Text style={[styles.greeting, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Hello,
                    </Text>
                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {user?.name || 'Student'}
                    </Text>
                    <Text style={[styles.class, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {studentInfo.class}
                    </Text>
                </View>
                <TouchableOpacity onPress={() => navigation.navigate('Notifications')}>
                    <Icon name="notifications" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
            </View>

            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
            >
                {/* Important Info */}
                <View style={styles.infoCardsRow}>
                    <Card style={[styles.infoCard, styles.attendanceCard]}>
                        <Icon name="event-available" size={32} color={colors.success} />
                        <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {studentInfo.attendance}%
                        </Text>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Attendance
                        </Text>
                    </Card>

                    <Card style={[styles.infoCard, styles.feeCard]}>
                        <Icon name="payment" size={32} color={studentInfo.feeBalance > 0 ? colors.error : colors.success} />
                        <Text style={[styles.infoValue, { color: studentInfo.feeBalance > 0 ? colors.error : colors.success }]}>
                            {formatters.formatCurrency(studentInfo.feeBalance)}
                        </Text>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Fee Balance
                        </Text>
                    </Card>
                </View>

                {/* Quick Actions */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Quick Access
                    </Text>
                    <View style={styles.actionsGrid}>
                        {quickActions.map(renderQuickAction)}
                    </View>
                </View>

                {/* Today's Schedule */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Today's Classes
                    </Text>
                    <Card>
                        {todaySchedule.map(renderScheduleItem)}
                    </Card>
                </View>

                {/* Pending Assignments */}
                {assignments.length > 0 && (
                    <View style={styles.section}>
                        <View style={styles.sectionHeader}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Pending Assignments ({assignments.length})
                            </Text>
                            <TouchableOpacity onPress={() => navigation.navigate('Assignments')}>
                                <Text style={[styles.viewAll, { color: colors.primary }]}>View All</Text>
                            </TouchableOpacity>
                        </View>

                        {assignments.map((assignment) => (
                            <Card key={assignment.id} style={styles.assignmentCard}>
                                <View style={styles.assignmentRow}>
                                    <Icon name="assignment" size={20} color={colors.primary} />
                                    <View style={styles.assignmentInfo}>
                                        <Text style={[styles.assignmentTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                            {assignment.title}
                                        </Text>
                                        <Text style={[styles.assignmentSubject, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {assignment.subject} • Due: {formatters.formatDate(assignment.dueDate)}
                                        </Text>
                                    </View>
                                </View>
                            </Card>
                        ))}
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    greeting: {
        fontSize: FONT_SIZES.sm,
    },
    name: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    class: {
        fontSize: FONT_SIZES.sm,
        marginTop: 2,
    },
    content: {
        padding: SPACING.xl,
    },
    infoCardsRow: {
        flexDirection: 'row',
        gap: SPACING.md,
    },
    infoCard: {
        flex: 1,
        alignItems: 'center',
        padding: SPACING.lg,
        gap: SPACING.xs,
    },
    attendanceCard: {},
    feeCard: {},
    infoValue: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    infoLabel: {
        fontSize: FONT_SIZES.xs,
    },
    section: {
        marginTop: SPACING.xl,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.md,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
    },
    viewAll: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    actionsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    actionCard: {
        flex: 1,
        minWidth: '45%',
        padding: SPACING.md,
        borderRadius: 12,
        alignItems: 'center',
        gap: SPACING.xs,
    },
    actionIcon: {
        width: 48,
        height: 48,
        borderRadius: 24,
        alignItems: 'center',
        justifyContent: 'center',
    },
    actionTitle: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
        fontWeight: '600',
    },
    scheduleItem: {
        flexDirection: 'row',
        paddingVertical: SPACING.md,
        borderBottomWidth: 1,
        gap: SPACING.md,
    },
    scheduleTime: {
        width: 80,
        fontWeight: '600',
        fontSize: FONT_SIZES.sm,
    },
    scheduleInfo: {
        flex: 1,
    },
    scheduleSubject: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    scheduleRoom: {
        fontSize: FONT_SIZES.sm,
        marginTop: 2,
    },
    assignmentCard: {
        marginBottom: SPACING.sm,
    },
    assignmentRow: {
        flexDirection: 'row',
        gap: SPACING.sm,
    },
    assignmentInfo: {
        flex: 1,
    },
    assignmentTitle: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    assignmentSubject: {
        fontSize: FONT_SIZES.xs,
        marginTop: 2,
    },
});
