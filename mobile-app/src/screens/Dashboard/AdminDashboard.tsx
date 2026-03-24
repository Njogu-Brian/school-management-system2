import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Dimensions,
    RefreshControl,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { dashboardApi, DashboardStats } from '@api/dashboard.api';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES, BORDER_RADIUS, COLORS } from '@constants/theme';
import { BRAND, RADIUS, SCREEN, CARD_STYLE } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

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
                styles.card,
                {
                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                    borderColor: isDark ? colors.borderDark : BRAND.border,
                    borderRadius: RADIUS.card,
                    width: CARD_WIDTH,
                    shadowColor: '#000',
                    shadowOffset: CARD_STYLE.shadowOffset,
                    shadowOpacity: CARD_STYLE.shadowOpacity,
                    shadowRadius: CARD_STYLE.shadowRadius,
                    elevation: CARD_STYLE.elevation,
                },
            ]}
            onPress={onPress}
            activeOpacity={0.7}
        >
            <View style={[styles.iconContainer, { backgroundColor: color + '20' }]}>
                <Icon name={icon} size={24} color={color} />
            </View>
            <Text style={[styles.cardValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {value}
            </Text>
            <Text style={[styles.cardTitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                {title}
            </Text>
        </TouchableOpacity>
    );
};

export const AdminDashboard = () => {
    const { user, logout } = useAuth();
    const { isDark, colors } = useTheme();
    const navigation = useNavigation<any>();
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

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

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : BRAND.bg }]}
        >
            <ScrollView
                contentContainerStyle={[styles.content, { paddingHorizontal: SCREEN.paddingHorizontal }]}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
                }
            >
                {/* Header */}
                <View style={styles.header}>
                    <View>
                        <Text style={[styles.greeting, { color: isDark ? colors.textSubDark : BRAND.muted }]}>
                            Welcome back,
                        </Text>
                        <Text style={[styles.name, { color: isDark ? colors.textMainDark : BRAND.text }]}>
                            {user?.name}
                        </Text>
                    </View>
                    <TouchableOpacity onPress={logout} style={styles.logoutButton}>
                        <Icon name="logout" size={HEADER.iconSize} color={BRAND.primary} />
                    </TouchableOpacity>
                </View>

                {/* Stats Cards */}
                <View style={styles.cardsContainer}>
                    <DashboardCard
                        title="Total Students"
                        value={loading ? '...' : stats ? stats.total_students.toLocaleString() : '0'}
                        icon="school"
                        color={BRAND.primary}
                        onPress={() => {
                            const nav = navigation.getParent() ?? navigation;
                            nav.navigate('Students', { screen: 'StudentsList' });
                        }}
                    />
                    <DashboardCard
                        title="Total Staff"
                        value={loading ? '...' : stats ? stats.total_staff.toLocaleString() : '0'}
                        icon="people"
                        color={BRAND.success}
                    />
                    <DashboardCard
                        title="Present Today"
                        value={loading ? '...' : stats ? stats.present_today.toLocaleString() : '0'}
                        icon="check-circle"
                        color={COLORS.warning}
                        onPress={() => {
                            const nav = navigation.getParent() ?? navigation;
                            nav.navigate('Attendance', { screen: 'MarkAttendance' });
                        }}
                    />
                    <DashboardCard
                        title="Fees Collected"
                        value={loading ? '...' : stats ? formatters.formatCurrency(stats.fees_collected) : formatters.formatCurrency(0)}
                        icon="payments"
                        color={BRAND.danger}
                        onPress={() => {
                            const nav = navigation.getParent() ?? navigation;
                            nav.navigate('Finance', { screen: 'InvoicesList' });
                        }}
                    />
                </View>

                {/* Quick Actions */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Quick Actions
                    </Text>
                    <View style={styles.actionsContainer}>
                        <TouchableOpacity
                            style={[
                                styles.actionButton,
                                {
                                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                    borderColor: isDark ? colors.borderDark : BRAND.border,
                                    borderWidth: 1,
                                    borderRadius: RADIUS.button,
                                },
                            ]}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Students', { screen: 'AddStudent' });
                            }}
                        >
                            <Icon name="add" size={20} color={BRAND.primary} />
                            <Text style={[styles.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Add Student
                            </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[
                                styles.actionButton,
                                {
                                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                    borderColor: isDark ? colors.borderDark : BRAND.border,
                                    borderWidth: 1,
                                    borderRadius: RADIUS.button,
                                },
                            ]}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Attendance', { screen: 'MarkAttendance' });
                            }}
                        >
                            <Icon name="fact-check" size={20} color={BRAND.primary} />
                            <Text style={[styles.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Mark Attendance
                            </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[styles.actionButton, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}
                            onPress={() => {
                                const nav = navigation.getParent() ?? navigation;
                                nav.navigate('Finance', { screen: 'InvoicesList' });
                            }}
                        >
                            <Icon name="receipt" size={20} color={colors.primary} />
                            <Text style={[styles.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Create Invoice
                            </Text>
                        </TouchableOpacity>
                    </View>
                </View>

                {/* Recent Activity */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Recent Activity
                    </Text>
                    <View
                        style={[
                            styles.activityContainer,
                            {
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                                borderRadius: RADIUS.card,
                            },
                        ]}
                    >
                        <Text style={[styles.emptyText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            No recent activity
                        </Text>
                    </View>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    content: {
        padding: SPACING.xl,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: SPACING.xl,
    },
    greeting: {
        fontSize: FONT_SIZES.sm,
        marginBottom: SPACING.xs,
    },
    name: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    logoutButton: {
        padding: SPACING.sm,
    },
    cardsContainer: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
        marginBottom: SPACING.xl,
    },
    card: {
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        alignItems: 'flex-start',
    },
    iconContainer: {
        width: 48,
        height: 48,
        borderRadius: BORDER_RADIUS.xl,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.md,
    },
    cardValue: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
        marginBottom: SPACING.xs,
    },
    cardTitle: {
        fontSize: FONT_SIZES.sm,
    },
    section: {
        marginBottom: SPACING.xl,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.md,
    },
    actionsContainer: {
        flexDirection: 'row',
        gap: SPACING.md,
    },
    actionButton: {
        flex: 1,
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        alignItems: 'center',
        gap: SPACING.xs,
    },
    actionText: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
    },
    activityContainer: {
        padding: SPACING.xl,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        minHeight: 100,
        justifyContent: 'center',
        alignItems: 'center',
    },
    emptyText: {
        fontSize: FONT_SIZES.sm,
    },
});
