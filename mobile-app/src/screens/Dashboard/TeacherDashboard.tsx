import React, { useMemo, useState, useCallback, useEffect } from 'react';
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
import { isSeniorTeacherRole } from '@utils/roleUtils';
import { getDashboardRoleLabel } from '@utils/dashboardRoleLabel';
import { Card } from '@components/common/Card';
import { DashboardHero, DashboardLineChart, DashboardBarChart, DashboardMenuGrid } from '@components/dashboard';
import type { DashboardMenuItem } from '@components/dashboard';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { tileColorForIndex, DASHBOARD_STAT_COLORS } from '@styles/sections/dashboard';
import { BRAND } from '@constants/designTokens';
import { dashboardApi, DashboardStats } from '@api/dashboard.api';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface TeacherDashboardProps {
    navigation: any;
}

export const TeacherDashboard: React.FC<TeacherDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);
    const [stats, setStats] = useState<DashboardStats | null>(null);

    const loadStats = useCallback(async () => {
        try {
            const res = await dashboardApi.getStats();
            if (res.success && res.data) {
                setStats(res.data);
            }
        } catch (e: any) {
            Alert.alert('Dashboard', e?.message || 'Could not load dashboard data.');
        }
    }, []);

    useEffect(() => {
        loadStats();
    }, [loadStats]);

    const handleRefresh = async () => {
        setRefreshing(true);
        await loadStats();
        setRefreshing(false);
    };

    const isSeniorTeacher = user?.role ? isSeniorTeacherRole(user.role) : false;
    const roleLabel = getDashboardRoleLabel(user?.role);

    const goTab = (tab: 'Home' | 'Classes' | 'Attendance' | 'More') => {
        navigation.navigate('Main', { screen: tab });
    };

    const baseActions = [
        { id: '1', title: 'Mark attendance', icon: 'event', onPress: () => goTab('Attendance') },
        { id: '2', title: 'Exams & marks', icon: 'edit', screen: 'MarksMatrixSetup' as const },
        { id: '3', title: 'Timetable', icon: 'schedule', screen: 'Timetable' as const },
        { id: '4', title: 'Assignments', icon: 'assignment', screen: 'Assignments' as const },
        { id: '5', title: 'Lesson plans', icon: 'menu-book', screen: 'LessonPlans' as const },
        { id: '6', title: 'My classes', icon: 'class', onPress: () => goTab('Classes') },
        { id: '7', title: 'Clock in / out', icon: 'access-time', screen: 'TeacherClock' as const },
        { id: '8', title: 'Transport', icon: 'directions-bus', screen: 'Transport' as const },
        { id: '9', title: 'Diary', icon: 'book', screen: 'Diary' as const },
        { id: '10', title: 'My profile', icon: 'person', screen: 'MyProfile' as const },
        { id: '11', title: 'My salary', icon: 'payments', screen: 'MySalary' as const },
        { id: '12', title: 'Leave', icon: 'event-busy', screen: 'Leave' as const },
    ];
    const seniorOnly = isSeniorTeacher
        ? [
              { id: '13', title: 'Supervised classes', icon: 'groups', screen: 'SupervisedClassrooms' as const },
              { id: '14', title: 'Supervised staff', icon: 'badge', screen: 'SupervisedStaff' as const },
              { id: '15', title: 'Fee balances', icon: 'account-balance-wallet', screen: 'FeeBalances' as const },
          ]
        : [];

    const menuItems: DashboardMenuItem[] = useMemo(
        () =>
            [...baseActions, ...seniorOnly].map((a, i) => ({
                id: a.id,
                title: a.title,
                icon: a.icon,
                color: tileColorForIndex(i),
                onPress:
                    'onPress' in a && a.onPress
                        ? a.onPress
                        : () => navigation.navigate((a as { screen: string }).screen),
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

    const lineChart = stats?.charts?.line ?? stats?.charts?.enrollment;
    const barChart = stats?.charts?.bar ?? stats?.charts?.payments;

    const myClasses = stats?.my_classes ?? 0;
    const totalStudents = stats?.total_students ?? 0;
    const pendingMarks = stats?.pending_marks ?? 0;
    const todayLessons = stats?.classes_today ?? 0;

    const firstSchedule = lineChart?.labels?.[0];

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
                        <KpiChip label="Classes" value={myClasses} icon="class" color={DASHBOARD_STAT_COLORS[0]} />
                        <KpiChip label="Students" value={totalStudents} icon="people" color={DASHBOARD_STAT_COLORS[1]} />
                        <KpiChip label="Pending marks" value={pendingMarks} icon="edit" color={DASHBOARD_STAT_COLORS[2]} />
                        <KpiChip label="Today" value={todayLessons} icon="schedule" color={DASHBOARD_STAT_COLORS[3]} />
                    </View>

                    {lineChart && lineChart.labels.length > 0 && (
                        <DashboardLineChart title="Recent activity" labels={lineChart.labels} data={lineChart.values} />
                    )}
                    {barChart && barChart.labels.length > 0 && (
                        <DashboardBarChart title="Students by class" labels={barChart.labels} data={barChart.values} />
                    )}

                    <DashboardMenuGrid title="Navigate" items={menuItems} />

                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Quick tab shortcuts
                    </Text>
                    <Card
                        style={{
                            ...styles.scheduleCard,
                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: isDark ? colors.borderDark : BRAND.border,
                        }}
                    >
                        <TouchableOpacity style={styles.scheduleItem} onPress={() => goTab('Classes')}>
                            <Icon name="school" size={22} color={colors.primary} />
                            <View style={styles.scheduleInfo}>
                                <Text
                                    style={[styles.scheduleSubject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}
                                >
                                    Class register
                                </Text>
                                <Text style={[styles.scheduleMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Open the Classes tab for your assigned streams
                                </Text>
                            </View>
                            <Icon name="chevron-right" size={22} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        </TouchableOpacity>
                        <TouchableOpacity style={styles.scheduleItem} onPress={() => goTab('Attendance')}>
                            <Icon name="fact-check" size={22} color={colors.primary} />
                            <View style={styles.scheduleInfo}>
                                <Text
                                    style={[styles.scheduleSubject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}
                                >
                                    Mark attendance
                                </Text>
                                <Text style={[styles.scheduleMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Use the Attendance tab — only your classes are listed
                                </Text>
                            </View>
                            <Icon name="chevron-right" size={22} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        </TouchableOpacity>
                        <TouchableOpacity style={styles.scheduleItem} onPress={() => goTab('More')}>
                            <Icon name="payments" size={22} color={colors.primary} />
                            <View style={styles.scheduleInfo}>
                                <Text
                                    style={[styles.scheduleSubject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}
                                >
                                    Pay, profile, leave
                                </Text>
                                <Text style={[styles.scheduleMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    My salary and full profile are under More
                                </Text>
                            </View>
                            <Icon name="chevron-right" size={22} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        </TouchableOpacity>
                    </Card>

                    {firstSchedule && (
                        <>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Teaching snapshot
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
                                        <Icon name="insights" size={18} color={colors.success} />
                                    </View>
                                    <Text
                                        style={[styles.activityText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
                                    >
                                        Charts reflect your assigned classes (and supervised campus if you are a senior teacher).
                                    </Text>
                                </View>
                            </Card>
                        </>
                    )}
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
        paddingVertical: SPACING.sm,
    },
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
