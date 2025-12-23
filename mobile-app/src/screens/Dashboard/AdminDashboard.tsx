import React from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Dimensions,
} from 'react-native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
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
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                    width: CARD_WIDTH,
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

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView contentContainerStyle={styles.content}>
                {/* Header */}
                <View style={styles.header}>
                    <View>
                        <Text style={[styles.greeting, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Welcome back,
                        </Text>
                        <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {user?.name}
                        </Text>
                    </View>
                    <TouchableOpacity onPress={logout} style={styles.logoutButton}>
                        <Icon name="logout" size={24} color={colors.primary} />
                    </TouchableOpacity>
                </View>

                {/* Stats Cards */}
                <View style={styles.cardsContainer}>
                    <DashboardCard title="Total Students" value="1,234" icon="school" color="#137fec" />
                    <DashboardCard title="Total Staff" value="87" icon="people" color="#10b981" />
                    <DashboardCard title="Present Today" value="1,156" icon="check-circle" color="#f59e0b" />
                    <DashboardCard title="Fees Collected" value="$45.2k" icon="payments" color="#ef4444" />
                </View>

                {/* Quick Actions */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Quick Actions
                    </Text>
                    <View style={styles.actionsContainer}>
                        <TouchableOpacity
                            style={[styles.actionButton, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}
                        >
                            <Icon name="add" size={20} color={colors.primary} />
                            <Text style={[styles.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Add Student
                            </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[styles.actionButton, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}
                        >
                            <Icon name="fact-check" size={20} color={colors.primary} />
                            <Text style={[styles.actionText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Mark Attendance
                            </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[styles.actionButton, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}
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
                                backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                borderColor: isDark ? colors.borderDark : colors.borderLight,
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
