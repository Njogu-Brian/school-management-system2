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
import { Avatar } from '@components/common/Avatar';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface ParentDashboardProps {
    navigation: any;
}

export const ParentDashboard: React.FC<ParentDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);

    // Mock data - in real app, fetch from API
    const [children] = useState([
        {
            id: 1,
            name: 'John Doe',
            class: 'Form 3A',
            feeBalance: 15000,
            attendance: 92,
            lastExam: 'Mid-Term',
            lastGrade: 'B+',
        },
        {
            id: 2,
            name: 'Jane Doe',
            class: 'Form 1B',
            feeBalance: 8500,
            attendance: 95,
            lastExam: 'CAT 1',
            lastGrade: 'A-',
        },
    ]);

    const [recentAnnouncements] = useState([
        { id: 1, title: 'Parent-Teacher Meeting', date: '2024-01-20' },
        { id: 2, title: 'Sports Day Postponed', date: '2024-01-18' },
    ]);

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 1000);
    };

    const quickActions = [
        { id: 1, title: 'Pay Fees', icon: 'payment', screen: 'PayFees', color: '#3b82f6' },
        { id: 2, title: 'View Results', icon: 'assessment', screen: 'Results', color: '#10b981' },
        { id: 3, title: 'Attendance', icon: 'event-available', screen: 'Attendance', color: '#f59e0b' },
        { id: 4, title: 'Fee Statement', icon: 'receipt', screen: 'FeeStatement', color: '#8b5cf6' },
    ];

    const renderChild = (child: any) => (
        <Card key={child.id} onPress={() => navigation.navigate('ChildDetail', { childId: child.id })}>
            <View style={styles.childCard}>
                <Avatar name={child.name} size={50} />

                <View style={styles.childInfo}>
                    <Text style={[styles.childName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {child.name}
                    </Text>
                    <Text style={[styles.childClass, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {child.class}
                    </Text>

                    <View style={styles.childStats}>
                        <View style={styles.stat}>
                            <Icon name="school" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.statText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {child.lastGrade}
                            </Text>
                        </View>
                        <View style={styles.stat}>
                            <Icon name="event-available" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.statText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {child.attendance}%
                            </Text>
                        </View>
                    </View>
                </View>

                <View style={styles.feeInfo}>
                    <Text style={[styles.feeLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Fee Balance
                    </Text>
                    <Text style={[styles.feeAmount, { color: child.feeBalance > 0 ? colors.error : colors.success }]}>
                        {formatters.formatCurrency(child.feeBalance)}
                    </Text>
                </View>
            </View>
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
                        Hello,
                    </Text>
                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {user?.name || 'Parent'}
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
                {/* My Children */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        My Children
                    </Text>
                    {children.map(renderChild)}
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

                {/* Recent Announcements */}
                <View style={styles.section}>
                    <View style={styles.sectionHeader}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Announcements
                        </Text>
                        <TouchableOpacity onPress={() => navigation.navigate('Announcements')}>
                            <Text style={[styles.viewAll, { color: colors.primary }]}>View All</Text>
                        </TouchableOpacity>
                    </View>

                    {recentAnnouncements.map((announcement) => (
                        <Card key={announcement.id} style={styles.announcementCard}>
                            <View style={styles.announcementRow}>
                                <Icon name="campaign" size={20} color={colors.primary} />
                                <View style={styles.announcementInfo}>
                                    <Text style={[styles.announcementTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {announcement.title}
                                    </Text>
                                    <Text style={[styles.announcementDate, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        {formatters.formatDate(announcement.date)}
                                    </Text>
                                </View>
                            </View>
                        </Card>
                    ))}
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
    section: {
        marginBottom: SPACING.xl,
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
    childCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.sm,
    },
    childInfo: {
        flex: 1,
        gap: 4,
    },
    childName: {
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
    },
    childClass: {
        fontSize: FONT_SIZES.sm,
    },
    childStats: {
        flexDirection: 'row',
        gap: SPACING.md,
        marginTop: 4,
    },
    stat: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    statText: {
        fontSize: FONT_SIZES.xs,
    },
    feeInfo: {
        alignItems: 'flex-end',
    },
    feeLabel: {
        fontSize: FONT_SIZES.xs,
    },
    feeAmount: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginTop: 2,
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
    announcementCard: {
        marginBottom: SPACING.sm,
    },
    announcementRow: {
        flexDirection: 'row',
        gap: SPACING.sm,
    },
    announcementInfo: {
        flex: 1,
    },
    announcementTitle: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    announcementDate: {
        fontSize: FONT_SIZES.xs,
        marginTop: 2,
    },
});
