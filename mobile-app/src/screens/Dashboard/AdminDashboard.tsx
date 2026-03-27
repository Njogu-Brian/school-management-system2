import React, { useState, useEffect, useMemo } from 'react';
import { View, Text, ScrollView, SafeAreaView, TouchableOpacity, Dimensions, RefreshControl } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { dashboardApi, DashboardStats } from '@api/dashboard.api';
import { formatters } from '@utils/formatters';
import { SPACING } from '@constants/theme';
import { BRAND, RADIUS, SCREEN, CARD_STYLE, HEADER } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { DashboardLineChart, DashboardBarChart } from '@components/dashboard';
import { mainBackgroundGradient, brandHeroGradient } from '@styles/screenGradients';
import { adminDashboardStyles as s } from '@styles/screens/adminDashboard.styles';

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
    const { user, logout } = useAuth();
    const { isDark, colors } = useTheme();
    const navigation = useNavigation<any>();
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const bgGradient = useMemo(() => [...mainBackgroundGradient(colors, isDark)] as [string, string, string], [colors, isDark]);
    const heroGradient = useMemo(() => [...brandHeroGradient(colors)] as [string, string], [colors]);

    const fetchStats = async () => {
        try {
            const res = await dashboardApi.getStats();
            if (res.success && res.data) setStats(res.data);
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

    const enrollment = useMemo(() => chartSeries(stats, 'enrollment'), [stats]);
    const payments = useMemo(() => chartSeries(stats, 'payments'), [stats]);
    const invoices = useMemo(() => chartSeries(stats, 'invoices'), [stats]);

    const birthdays = stats?.birthdays ?? [];
    const onLeave = stats?.teachers_on_leave ?? [];

    return (
        <LinearGradient colors={bgGradient} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={s.gradient}>
            <SafeAreaView style={s.flex}>
                <ScrollView
                    contentContainerStyle={[s.scrollContent, { paddingHorizontal: SCREEN.paddingHorizontal }]}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
                    }
                    showsVerticalScrollIndicator={false}
                >
                    <LinearGradient
                        colors={heroGradient}
                        start={{ x: 0, y: 0 }}
                        end={{ x: 1, y: 1 }}
                        style={s.headerAccent}
                    >
                        <View style={s.header}>
                            <View style={{ flex: 1 }}>
                                <Text style={[s.greeting, { color: 'rgba(255,255,255,0.9)' }]}>Welcome back,</Text>
                                <Text style={[s.name, { color: '#fff' }]}>{user?.name}</Text>
                            </View>
                            <View style={{ flexDirection: 'row', alignItems: 'center', gap: SPACING.sm }}>
                                <TouchableOpacity
                                    onPress={() => {
                                        const nav = navigation.getParent() ?? navigation;
                                        nav.navigate('More', { screen: 'Settings' });
                                    }}
                                    style={[s.logoutButton, { backgroundColor: 'rgba(255,255,255,0.2)' }]}
                                >
                                    <Icon name="settings" size={HEADER.iconSize} color="#fff" />
                                </TouchableOpacity>
                                <TouchableOpacity
                                    onPress={logout}
                                    style={[s.logoutButton, { backgroundColor: 'rgba(255,255,255,0.2)' }]}
                                >
                                    <Icon name="logout" size={HEADER.iconSize} color="#fff" />
                                </TouchableOpacity>
                            </View>
                        </View>
                    </LinearGradient>

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
                            title="Total Staff"
                            value={loading ? '…' : stats?.total_staff != null ? stats.total_staff.toLocaleString() : '0'}
                            icon="people"
                            color={BRAND.success}
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
                            title="Fees Collected"
                            value={loading ? '…' : formatters.formatCurrency(stats?.fees_collected ?? 0)}
                            icon="payments"
                            color={BRAND.danger}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Finance', { screen: 'InvoicesList' });
                            }}
                        />
                    </View>

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
                </ScrollView>
            </SafeAreaView>
        </LinearGradient>
    );
};
