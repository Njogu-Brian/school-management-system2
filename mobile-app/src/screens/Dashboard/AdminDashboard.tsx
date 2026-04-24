import React, { useState, useEffect, useMemo } from 'react';
import { View, Text, ScrollView, SafeAreaView, TouchableOpacity, Dimensions, RefreshControl } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { dashboardApi, DashboardStats } from '@api/dashboard.api';
import { formatters } from '@utils/formatters';
import { getDashboardRoleLabel } from '@utils/dashboardRoleLabel';
import { SPACING } from '@constants/theme';
import { BRAND, RADIUS, SCREEN, CARD_STYLE } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { DashboardHero, DashboardLineChart, DashboardBarChart, DashboardMenuGrid } from '@components/dashboard';
import type { DashboardMenuItem } from '@components/dashboard';
import { mainBackgroundGradient } from '@styles/screenGradients';
import { adminDashboardStyles as s } from '@styles/screens/adminDashboard.styles';
import { tileColorForIndex } from '@styles/sections/dashboard';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - SPACING.xl * 2 - SPACING.md) / 2;

interface DashboardCardProps {
    title: string;
    value: string | number;
    icon: string;
    color: string;
    onPress?: () => void;
}

const DashboardCard: React.FC<DashboardCardProps> = ({ title, value, icon, color, onPress }) => {
    const { isDark, colors } = useTheme();

    return (
        <TouchableOpacity
            style={[
                s.card,
                {
                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                    borderColor: isDark ? colors.borderDark : BRAND.border,
                    borderRadius: RADIUS.card,
                    width: CARD_WIDTH,
                    borderWidth: 1,
                    shadowColor: Palette.shadowIOS,
                    shadowOffset: CARD_STYLE.shadowOffset,
                    shadowOpacity: CARD_STYLE.shadowOpacity,
                    shadowRadius: CARD_STYLE.shadowRadius,
                    elevation: CARD_STYLE.elevation,
                },
            ]}
            onPress={onPress}
            activeOpacity={0.7}
        >
            <View style={[s.iconContainer, { backgroundColor: color + '22' }]}>
                <Icon name={icon} size={24} color={color} />
            </View>
            <Text style={[s.cardValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{value}</Text>
            <Text style={[s.cardTitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>{title}</Text>
        </TouchableOpacity>
    );
};

function chartSeries(
    stats: DashboardStats | null,
    key: 'enrollment' | 'payments' | 'invoices'
): { labels: string[]; values: number[] } {
    const c = stats?.charts?.[key];
    if (c?.labels?.length && c.values?.length) {
        return { labels: c.labels, values: c.values };
    }
    return { labels: [], values: [] };
}

export const AdminDashboard = () => {
    const { user } = useAuth();
    const { isDark, colors } = useTheme();
    const navigation = useNavigation<any>();
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const bgGradient = useMemo(() => [...mainBackgroundGradient(colors, isDark)] as [string, string, string], [colors, isDark]);

    const greeting = useMemo(() => {
        const h = new Date().getHours();
        if (h < 12) return 'Good morning';
        if (h < 17) return 'Good afternoon';
        return 'Good evening';
    }, []);

    const [filterYearId, setFilterYearId] = useState<number | null>(null);
    const [filterTermId, setFilterTermId] = useState<number | null>(null);

    const fetchStats = async (yearId?: number | null, termId?: number | null) => {
        try {
            const res = await dashboardApi.getStats({
                academic_year_id: yearId !== undefined ? yearId : filterYearId,
                term_id: termId !== undefined ? termId : filterTermId,
            });
            if (res.success && res.data) {
                setStats(res.data);
                if (res.data.filters) {
                    if (yearId === undefined && filterYearId == null && res.data.filters.academic_year_id != null) {
                        setFilterYearId(res.data.filters.academic_year_id);
                    }
                }
            }
        } catch {
            setStats(null);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        fetchStats();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        fetchStats();
    };

    const availableYears = stats?.filters?.available_years ?? [];
    const availableTerms = (stats?.filters?.available_terms ?? []).filter((t) =>
        filterYearId ? t.academic_year_id === filterYearId : true
    );

    const cycleYear = () => {
        if (!availableYears.length) return;
        const idx = availableYears.findIndex((y) => y.id === filterYearId);
        const next = availableYears[(idx + 1) % availableYears.length];
        setFilterYearId(next.id);
        setFilterTermId(null);
        setRefreshing(true);
        fetchStats(next.id, null);
    };

    const cycleTerm = () => {
        if (!availableTerms.length) return;
        const idx = availableTerms.findIndex((t) => t.id === filterTermId);
        const next = filterTermId == null ? availableTerms[0] : availableTerms[(idx + 1) % (availableTerms.length + 1)];
        const nextId = !next || idx + 1 >= availableTerms.length ? null : next.id;
        setFilterTermId(nextId);
        setRefreshing(true);
        fetchStats(filterYearId, nextId);
    };

    const selectedYearLabel = availableYears.find((y) => y.id === filterYearId)?.year ?? 'All';
    const selectedTermLabel = availableTerms.find((t) => t.id === filterTermId)?.name ?? 'Whole year';

    const navItems: DashboardMenuItem[] = useMemo(() => {
        const items = [
            { id: 'students', title: 'Students', icon: 'school', to: ['Students', 'StudentsList'] as const },
            { id: 'add', title: 'Add student', icon: 'person-add', to: ['Students', 'AddStudent'] as const },
            { id: 'staff', title: 'Staff', icon: 'badge', to: ['More', 'StaffDirectory'] as const },
            { id: 'attendance', title: 'Attendance', icon: 'fact-check', to: ['Attendance', 'MarkAttendance'] as const },
            { id: 'invoices', title: 'Invoices', icon: 'receipt-long', to: ['Finance', 'InvoicesList'] as const },
            { id: 'payments', title: 'Payments', icon: 'payments', to: ['Finance', 'PaymentsList'] as const },
            { id: 'fees', title: 'Fee structures', icon: 'attach-money', to: ['Finance', 'FeeStructures'] as const },
            { id: 'reqs', title: 'Requirements', icon: 'inventory-2', to: ['More', 'TeacherRequirements'] as const },
            { id: 'transport', title: 'Transport', icon: 'directions-bus', to: ['More', 'RoutesList'] as const },
            { id: 'leave', title: 'Leave', icon: 'event-busy', to: ['More', 'LeaveManagement'] as const },
            { id: 'payroll', title: 'Payroll', icon: 'account-balance-wallet', to: ['More', 'PayrollRecords'] as const },
            { id: 'announcements', title: 'Announcements', icon: 'campaign', to: ['More', 'Announcements'] as const },
            { id: 'notifications', title: 'Notifications', icon: 'notifications', to: ['More', 'Notifications'] as const },
            { id: 'settings', title: 'Settings', icon: 'settings', to: ['More', 'Settings'] as const },
        ];
        return items.map((item, i) => ({
            id: item.id,
            title: item.title,
            icon: item.icon,
            color: tileColorForIndex(i),
            onPress: () => {
                const nav = navigation.getParent() ?? navigation;
                nav.navigate(item.to[0], { screen: item.to[1] });
            },
        }));
    }, [navigation]);

    const enrollment = useMemo(() => chartSeries(stats, 'enrollment'), [stats]);
    const payments = useMemo(() => chartSeries(stats, 'payments'), [stats]);
    const invoices = useMemo(() => chartSeries(stats, 'invoices'), [stats]);

    const birthdays = stats?.birthdays ?? [];
    const onLeave = stats?.teachers_on_leave ?? [];

    return (
        <LinearGradient colors={bgGradient} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={s.gradient}>
            <SafeAreaView style={s.flex}>
                <ScrollView
                    contentContainerStyle={{ paddingBottom: SPACING.xxl }}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
                    }
                    showsVerticalScrollIndicator={false}
                >
                    <DashboardHero
                        greeting={greeting}
                        userName={user?.name || 'Admin'}
                        roleLabel={getDashboardRoleLabel(user?.role)}
                        avatarUrl={user?.avatar}
                        onPressNotifications={() => {
                            const nav = navigation.getParent() ?? navigation;
                            nav.navigate('More', { screen: 'Notifications' });
                        }}
                        onPressSettings={() => {
                            const nav = navigation.getParent() ?? navigation;
                            nav.navigate('More', { screen: 'Settings' });
                        }}
                    />

                    <View style={{ paddingHorizontal: SCREEN.paddingHorizontal, paddingTop: SPACING.lg }}>

                    {/* Academic year / term filter pills */}
                    <View style={{ flexDirection: 'row', gap: SPACING.sm, marginBottom: SPACING.md }}>
                        <TouchableOpacity
                            onPress={cycleYear}
                            style={{
                                flex: 1,
                                paddingVertical: SPACING.sm,
                                paddingHorizontal: SPACING.md,
                                borderRadius: RADIUS.button,
                                borderWidth: 1,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                flexDirection: 'row',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                            }}
                        >
                            <View>
                                <Text style={{ fontSize: 11, color: isDark ? colors.textSubDark : colors.textSubLight }}>
                                    Academic year
                                </Text>
                                <Text style={{ fontSize: 14, fontWeight: '700', color: isDark ? colors.textMainDark : colors.textMainLight }}>
                                    {String(selectedYearLabel)}
                                </Text>
                            </View>
                            <Icon name="swap-horiz" size={20} color={colors.primary} />
                        </TouchableOpacity>
                        <TouchableOpacity
                            onPress={cycleTerm}
                            style={{
                                flex: 1,
                                paddingVertical: SPACING.sm,
                                paddingHorizontal: SPACING.md,
                                borderRadius: RADIUS.button,
                                borderWidth: 1,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                flexDirection: 'row',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                            }}
                        >
                            <View>
                                <Text style={{ fontSize: 11, color: isDark ? colors.textSubDark : colors.textSubLight }}>
                                    Term
                                </Text>
                                <Text style={{ fontSize: 14, fontWeight: '700', color: isDark ? colors.textMainDark : colors.textMainLight }}>
                                    {selectedTermLabel}
                                </Text>
                            </View>
                            <Icon name="swap-horiz" size={20} color={colors.primary} />
                        </TouchableOpacity>
                    </View>

                    <View style={s.cardsContainer}>
                        <DashboardCard
                            title="Total Students"
                            value={loading ? '…' : stats?.total_students != null ? stats.total_students.toLocaleString() : '0'}
                            icon="school"
                            color={colors.primary}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Students', { screen: 'StudentsList' });
                            }}
                        />
                        <DashboardCard
                            title="Present Today"
                            value={loading ? '…' : stats?.present_today != null ? stats.present_today.toLocaleString() : '0'}
                            icon="check-circle"
                            color={colors.warning}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Attendance', { screen: 'MarkAttendance' });
                            }}
                        />
                        <DashboardCard
                            title="Total Invoiced"
                            value={loading ? '…' : formatters.formatCurrency(stats?.total_invoiced ?? 0)}
                            icon="receipt-long"
                            color={colors.info}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Finance', { screen: 'InvoicesList' });
                            }}
                        />
                        <DashboardCard
                            title="Total Payments"
                            value={loading ? '…' : formatters.formatCurrency(stats?.total_payments ?? stats?.fees_collected ?? 0)}
                            icon="payments"
                            color={BRAND.success}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Finance', { screen: 'PaymentsList' });
                            }}
                        />
                        <DashboardCard
                            title="Outstanding Balance"
                            value={loading ? '…' : formatters.formatCurrency(stats?.outstanding_balance ?? 0)}
                            icon="account-balance-wallet"
                            color={BRAND.danger}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Finance', { screen: 'InvoicesList' });
                            }}
                        />
                        <DashboardCard
                            title="Total Staff"
                            value={loading ? '…' : stats?.total_staff != null ? stats.total_staff.toLocaleString() : '0'}
                            icon="people"
                            color={BRAND.success}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('More', { screen: 'StaffDirectory' });
                            }}
                        />
                    </View>

                    <DashboardMenuGrid title="Navigate" items={navItems} />

                    {enrollment.labels.length > 0 && (
                        <DashboardLineChart
                            title="New enrolments (by month)"
                            labels={enrollment.labels}
                            data={enrollment.values}
                        />
                    )}

                    {payments.labels.length > 0 && (
                        <DashboardBarChart title="Payments received (by month)" labels={payments.labels} data={payments.values} />
                    )}

                    {invoices.labels.length > 0 && (
                        <DashboardLineChart
                            title="Invoices issued (by month)"
                            labels={invoices.labels}
                            data={invoices.values}
                        />
                    )}

                    <View style={s.section}>
                        <Text style={[s.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Upcoming birthdays
                        </Text>
                        {birthdays.length === 0 ? (
                            <View style={[s.emptyBox, { borderColor: colors.borderDark, backgroundColor: isDark ? colors.surfaceDark : BRAND.surface }]}>
                                <Text style={[s.emptyText, { color: isDark ? colors.textSubDark : BRAND.muted }]}>
                                    No birthdays in the next 14 days
                                </Text>
                            </View>
                        ) : (
                            birthdays.map((b, idx) => (
                                <View
                                    key={`${b.name}-${b.date}-${idx}`}
                                    style={[
                                        s.listCard,
                                        {
                                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                            borderColor: isDark ? colors.borderDark : BRAND.border,
                                        },
                                    ]}
                                >
                                    <View style={[s.listIconWrap, { backgroundColor: (b.type === 'student' ? colors.info : colors.warning) + '22' }]}>
                                        <Icon name="cake" size={22} color={b.type === 'student' ? colors.info : colors.warning} />
                                    </View>
                                    <View style={s.listMain}>
                                        <Text style={[s.listTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                            {b.name}
                                        </Text>
                                        <Text style={[s.listSub, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {formatters.formatDate(b.date)} · {b.type === 'student' ? 'Student' : 'Staff'}
                                        </Text>
                                    </View>
                                </View>
                            ))
                        )}
                    </View>

                    <View style={s.section}>
                        <Text style={[s.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Teachers on leave
                        </Text>
                        {onLeave.length === 0 ? (
                            <View style={[s.emptyBox, { borderColor: colors.borderDark, backgroundColor: isDark ? colors.surfaceDark : BRAND.surface }]}>
                                <Text style={[s.emptyText, { color: isDark ? colors.textSubDark : BRAND.muted }]}>
                                    No teachers on leave today
                                </Text>
                            </View>
                        ) : (
                            onLeave.map((row, idx) => (
                                <View
                                    key={`${row.name}-${row.start_date}-${idx}`}
                                    style={[
                                        s.listCard,
                                        {
                                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                            borderColor: isDark ? colors.borderDark : BRAND.border,
                                        },
                                    ]}
                                >
                                    <View style={[s.listIconWrap, { backgroundColor: colors.success + '22' }]}>
                                        <Icon name="event-busy" size={22} color={colors.success} />
                                    </View>
                                    <View style={s.listMain}>
                                        <Text style={[s.listTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                            {row.name}
                                        </Text>
                                        <Text style={[s.listSub, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {row.leave_type || 'Leave'} · {formatters.formatDate(row.start_date)} –{' '}
                                            {formatters.formatDate(row.end_date)}
                                        </Text>
                                    </View>
                                </View>
                            ))
                        )}
                    </View>

                    <View style={s.section}>
                        <Text style={[s.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Quick actions
                        </Text>
                        <View style={s.actionsContainer}>
                            <TouchableOpacity
                                style={[
                                    s.actionButton,
                                    {
                                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                        borderWidth: 1,
                                        borderRadius: RADIUS.button,
                                        shadowColor: Palette.shadowIOS,
                                        shadowOffset: { width: 0, height: 2 },
                                        shadowOpacity: 0.08,
                                        shadowRadius: 6,
                                        elevation: 2,
                                    },
                                ]}
                                onPress={() => {
                                    const nav = navigation.getParent() ?? navigation;
                                    nav.navigate('Students', { screen: 'AddStudent' });
                                }}
                            >
                                <Icon name="add" size={20} color={colors.primary} />
                                <Text style={[s.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Add Student
                                </Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[
                                    s.actionButton,
                                    {
                                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                        borderWidth: 1,
                                        borderRadius: RADIUS.button,
                                        shadowColor: Palette.shadowIOS,
                                        shadowOffset: { width: 0, height: 2 },
                                        shadowOpacity: 0.08,
                                        shadowRadius: 6,
                                        elevation: 2,
                                    },
                                ]}
                                onPress={() => {
                                    const nav = navigation.getParent() ?? navigation;
                                    nav.navigate('Attendance', { screen: 'MarkAttendance' });
                                }}
                            >
                                <Icon name="fact-check" size={20} color={colors.primary} />
                                <Text style={[s.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Attendance
                                </Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[
                                    s.actionButton,
                                    {
                                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                        borderWidth: 1,
                                        borderRadius: RADIUS.button,
                                        shadowColor: Palette.shadowIOS,
                                        shadowOffset: { width: 0, height: 2 },
                                        shadowOpacity: 0.08,
                                        shadowRadius: 6,
                                        elevation: 2,
                                    },
                                ]}
                                onPress={() => {
                                    const nav = navigation.getParent() ?? navigation;
                                    nav.navigate('Finance', { screen: 'InvoicesList' });
                                }}
                            >
                                <Icon name="receipt-long" size={20} color={colors.primary} />
                                <Text style={[s.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Invoices
                                </Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                    </View>
                </ScrollView>
            </SafeAreaView>
        </LinearGradient>
    );
};
