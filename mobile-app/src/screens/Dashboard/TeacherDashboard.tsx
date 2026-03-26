import React, { useMemo, useState } from 'react';
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
import { isSeniorTeacherRole } from '@utils/roleUtils';
import { getDashboardRoleLabel } from '@utils/dashboardRoleLabel';
import { Card } from '@components/common/Card';
import { DashboardHero, DashboardLineChart, DashboardBarChart, DashboardMenuGrid } from '@components/dashboard';
import type { DashboardMenuItem } from '@components/dashboard';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { tileColorForIndex, DASHBOARD_STAT_COLORS } from '@styles/sections/dashboard';
import { BRAND } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface TeacherDashboardProps {
    navigation: any;
}

export const TeacherDashboard: React.FC<TeacherDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);

    const [stats] = useState({
        myClasses: 5,
        totalStudents: 150,
        pendingMarks: 12,
        todayLessons: 4,
    });

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 1000);
    };

    const isSeniorTeacher = user?.role ? isSeniorTeacherRole(user.role) : false;
    const roleLabel = getDashboardRoleLabel(user?.role);

    const baseActions = [
        { id: '1', title: 'Mark Attendance', icon: 'event', screen: 'MarkAttendance' },
        { id: '2', title: 'Exams & Marks', icon: 'edit', screen: 'ExamsList' },
        { id: '3', title: 'Timetable', icon: 'schedule', screen: 'Timetable' },
        { id: '4', title: 'Assignments', icon: 'assignment', screen: 'Assignments' },
        { id: '5', title: 'Lesson Plans', icon: 'menu-book', screen: 'LessonPlans' },
        { id: '6', title: 'My Classes', icon: 'class', screen: 'MyClasses' },
        { id: '7', title: 'Transport', icon: 'directions-bus', screen: 'Transport' },
        { id: '8', title: 'Diary', icon: 'book', screen: 'Diary' },
        { id: '9', title: 'My Profile', icon: 'person', screen: 'MyProfile' },
        { id: '10', title: 'My Salary', icon: 'payments', screen: 'MySalary' },
        { id: '11', title: 'Leave', icon: 'event-busy', screen: 'Leave' },
    ];
    const seniorOnly = isSeniorTeacher
        ? [
              { id: '12', title: 'Supervised Classes', icon: 'groups', screen: 'SupervisedClassrooms' },
              { id: '13', title: 'Supervised Staff', icon: 'badge', screen: 'SupervisedStaff' },
              { id: '14', title: 'Fee Balances', icon: 'account-balance-wallet', screen: 'FeeBalances' },
          ]
        : [];

    const menuItems: DashboardMenuItem[] = useMemo(
        () =>
            [...baseActions, ...seniorOnly].map((a, i) => ({
                id: a.id,
                title: a.title,
                icon: a.icon,
                color: tileColorForIndex(i),
                onPress: () => navigation.navigate(a.screen),
            })),
        [isSeniorTeacher, navigation]
    );

    const greeting = useMemo(() => {
        const h = new Date().getHours();
        if (h < 12) return 'Good morning';
        if (h < 17) return 'Good afternoon';
        return 'Good evening';
    }, []);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;

    return (
        <SafeAreaView style={[styles.root, { backgroundColor: bg }]}>
            <ScrollView
                showsVerticalScrollIndicator={false}
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
            >
                <DashboardHero
                    greeting={greeting}
                    userName={user?.name || 'Teacher'}
                    roleLabel={roleLabel}
                    avatarUrl={user?.avatar}
                    onPressNotifications={() => navigation.navigate('Notifications')}
                    onPressSettings={() => navigation.navigate('Settings')}
                />

                <View style={styles.body}>
                    <View style={styles.kpiRow}>
                        <KpiChip label="Classes" value={stats.myClasses} icon="class" color={DASHBOARD_STAT_COLORS[0]} />
                        <KpiChip label="Students" value={stats.totalStudents} icon="people" color={DASHBOARD_STAT_COLORS[1]} />
                        <KpiChip label="Pending marks" value={stats.pendingMarks} icon="edit" color={DASHBOARD_STAT_COLORS[2]} />
                        <KpiChip label="Today" value={stats.todayLessons} icon="schedule" color={DASHBOARD_STAT_COLORS[3]} />
                    </View>

                    <DashboardLineChart
                        title="Teaching load (hours, this week)"
                        labels={['Mon', 'Tue', 'Wed', 'Thu', 'Fri']}
                        data={[4, 5, 3, 6, 4]}
                    />
                    <DashboardBarChart
                        title="Classes per subject"
                        labels={['Math', 'Eng', 'Sci', 'Art']}
                        data={[2, 1, 2, 1]}
                    />

                    <DashboardMenuGrid title="Navigate" items={menuItems} />

                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Today&apos;s schedule
                    </Text>
                    <Card
                        style={{
                            ...styles.scheduleCard,
                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: isDark ? colors.borderDark : BRAND.border,
                        }}
                    >
                        <View style={styles.scheduleItem}>
                            <View style={styles.timeBlock}>
                                <Text style={[styles.time, { color: colors.primary }]}>08:00</Text>
                                <Text style={[styles.timeAmpm, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    AM
                                </Text>
                            </View>
                            <View style={styles.scheduleInfo}>
                                <Text
                                    style={[styles.scheduleSubject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}
                                >
                                    Mathematics
                                </Text>
                                <Text style={[styles.scheduleMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Form 3A · Room 12
                                </Text>
                            </View>
                            <Icon name="chevron-right" size={22} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        </View>
                    </Card>

                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Recent activity
                    </Text>
                    <Card
                        style={{
                            ...styles.activityCard,
                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: isDark ? colors.borderDark : BRAND.border,
                        }}
                    >
                        <View style={styles.activityRow}>
                            <View style={[styles.activityDot, { backgroundColor: colors.success + '33' }]}>
                                <Icon name="assignment-turned-in" size={18} color={colors.success} />
                            </View>
                            <Text style={[styles.activityText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Marks submitted for Mathematics — syncs when the API is connected.
                            </Text>
                        </View>
                    </Card>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const KpiChip: React.FC<{ label: string; value: number; icon: string; color: string }> = ({ label, value, icon, color }) => {
    const { isDark, colors } = useTheme();
    return (
        <View
            style={[
                styles.kpiChip,
                {
                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                    borderColor: isDark ? colors.borderDark : BRAND.border,
                },
            ]}
        >
            <View style={[styles.kpiIcon, { backgroundColor: color + '22' }]}>
                <Icon name={icon} size={20} color={color} />
            </View>
            <Text style={[styles.kpiValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{value}</Text>
            <Text style={[styles.kpiLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]} numberOfLines={1}>
                {label}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    root: { flex: 1 },
    scroll: { paddingBottom: SPACING.xxl },
    body: {
        paddingHorizontal: SPACING.xl,
        paddingTop: SPACING.lg,
    },
    kpiRow: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
        marginBottom: SPACING.lg,
    },
    kpiChip: {
        width: '47%',
        flexGrow: 1,
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        alignItems: 'center',
    },
    kpiIcon: {
        width: 40,
        height: 40,
        borderRadius: BORDER_RADIUS.lg,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.xs,
    },
    kpiValue: {
        fontSize: FONT_SIZES.xl,
        fontWeight: '800',
    },
    kpiLabel: {
        fontSize: FONT_SIZES.xs,
        marginTop: 2,
        textAlign: 'center',
    },
    sectionTitle: {
        fontSize: FONT_SIZES.md,
        fontWeight: '800',
        marginBottom: SPACING.sm,
        marginTop: SPACING.sm,
    },
    scheduleCard: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.md,
    },
    scheduleItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    timeBlock: { alignItems: 'flex-start', minWidth: 56 },
    time: { fontSize: FONT_SIZES.md, fontWeight: '800' },
    timeAmpm: { fontSize: FONT_SIZES.xs, marginTop: -2 },
    scheduleInfo: { flex: 1 },
    scheduleSubject: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    scheduleMeta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    activityCard: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.xl,
    },
    activityRow: { flexDirection: 'row', alignItems: 'flex-start', gap: SPACING.md },
    activityDot: {
        width: 36,
        height: 36,
        borderRadius: 18,
        alignItems: 'center',
        justifyContent: 'center',
    },
    activityText: { flex: 1, fontSize: FONT_SIZES.sm, lineHeight: 20 },
});
