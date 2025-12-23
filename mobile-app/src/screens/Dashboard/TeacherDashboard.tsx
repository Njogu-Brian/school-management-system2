import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface TeacherDashboardProps {
    navigation: any;
}

export const TeacherDashboard: React.FC<TeacherDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);

    const [stats, setStats] = useState({
        myClasses: 5,
        totalStudents: 150,
        pendingMarks: 12,
        todayLessons: 4,
        pendingAssignments: 8,
    });

    const handleRefresh = () => {
        setRefreshing(true);
        // Load dashboard data
        setTimeout(() => setRefreshing(false), 1000);
    };

    const quickActions = [
        { id: 1, title: 'Mark Attendance', icon: 'event', screen: 'MarkAttendance', color: '#3b82f6' },
        { id: 2, title: 'Enter Marks', icon: 'edit', screen: 'MarksEntry', color: '#10b981' },
        { id: 3, title: 'My Timetable', icon: 'schedule', screen: 'Timetable', color: '#f59e0b' },
        { id: 4, title: 'Assignments', icon: 'assignment', screen: 'Assignments', color: '#8b5cf6' },
        { id: 5, title: 'Lesson Plans', icon: 'menu-book', screen: 'LessonPlans', color: '#ec4899' },
        { id: 6, title: 'My Classes', icon: 'class', screen: 'MyClasses', color: '#14b8a6' },
    ];

    const renderStatCard = (title: string, value: number, icon: string, color: string) => (
        <Card style={[styles.statCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
            <View style={[styles.iconContainer, { backgroundColor: color + '20' }]}>
                <Icon name={icon} size={28} color={color} />
            </View>
            <Text style={[styles.statValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {value}
            </Text>
            <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                {title}
            </Text>
        </Card>
    );

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

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <View>
                    <Text style={[styles.greeting, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Welcome back,
                    </Text>
                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {user?.name || 'Teacher'}
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
                {/* Stats */}
                <View style={styles.statsGrid}>
                    {renderStatCard('My Classes', stats.myClasses, 'class', '#3b82f6')}
                    {renderStatCard('Students', stats.totalStudents, 'people', '#10b981')}
                    {renderStatCard('Pending Marks', stats.pendingMarks, 'edit', '#f59e0b')}
                    {renderStatCard('Today\'s Lessons', stats.todayLessons, 'schedule', '#8b5cf6')}
                </View>

                {/* Quick Actions */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Quick Actions
                    </Text>
                    <View style={styles.actionsGrid}>
                        {quickActions.map(renderQuickAction)}
                    </View>
                </View>

                {/* Today's Schedule */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Today's Schedule
                    </Text>
                    <Card>
                        <View style={styles.scheduleItem}>
                            <View style={styles.timeBlock}>
                                <Text style={[styles.time, { color: colors.primary }]}>08:00 AM</Text>
                            </View>
                            <View style={styles.scheduleInfo}>
                                <Text style={[styles.scheduleSubject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Mathematics
                                </Text>
                                <Text style={[styles.scheduleClass, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Form 3A • Room 12
                                </Text>
                            </View>
                        </View>
                    </Card>
                </View>

                {/* Recent Activity */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Recent Activity
                    </Text>
                    <Card>
                        <View style={styles.activityItem}>
                            <Icon name="assignment-turned-in" size={20} color={colors.success} />
                            <Text style={[styles.activityText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                You submitted marks for Mathematics exam
                            </Text>
                        </View>
                    </Card>
                </View>
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
    content: {
        padding: SPACING.xl,
    },
    statsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    statCard: {
        flex: 1,
        minWidth: '45%',
        padding: SPACING.md,
        borderRadius: 12,
        alignItems: 'center',
        gap: SPACING.xs,
    },
    iconContainer: {
        width: 56,
        height: 56,
        borderRadius: 28,
        alignItems: 'center',
        justifyContent: 'center',
    },
    statValue: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    statLabel: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
    },
    section: {
        marginTop: SPACING.xl,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.md,
    },
    actionsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    actionCard: {
        flex: 1,
        minWidth: '30%',
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
        gap: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    timeBlock: {
        width: 80,
        justifyContent: 'center',
    },
    time: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    scheduleInfo: {
        flex: 1,
    },
    scheduleSubject: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    scheduleClass: {
        fontSize: FONT_SIZES.sm,
    },
    activityItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        paddingVertical: SPACING.sm,
    },
    activityText: {
        flex: 1,
        fontSize: FONT_SIZES.sm,
    },
});
