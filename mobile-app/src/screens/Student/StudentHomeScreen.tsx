import React, { useMemo } from 'react';
import {
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    View,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { formatters } from '@utils/formatters';
import { getDashboardRoleLabel } from '@utils/dashboardRoleLabel';
import { DashboardHero, DashboardLineChart, DashboardBarChart, DashboardMenuGrid } from '@components/dashboard';
import type { DashboardMenuItem } from '@components/dashboard';
import { tileColorForIndex } from '@styles/sections/dashboard';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { getParent: () => { navigate: (name: string, params?: object) => void } | null };
}

export const StudentHomeScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const parentNav = () => navigation.getParent();

    const studentInfo = useMemo(
        () => ({
            className: 'Form 3A',
            attendance: 92,
            feeBalance: 15000,
            pendingAssignments: 3,
        }),
        []
    );

    const menuItems: DashboardMenuItem[] = useMemo(
        () => [
            {
                id: 'hw',
                title: 'Homework',
                icon: 'assignment',
                color: tileColorForIndex(0),
                onPress: () => parentNav()?.navigate('StudentHomeworkTab'),
            },
            {
                id: 'res',
                title: 'Results',
                icon: 'grade',
                color: tileColorForIndex(1),
                onPress: () => parentNav()?.navigate('StudentResultsTab'),
            },
            {
                id: 'ann',
                title: 'News',
                icon: 'campaign',
                color: tileColorForIndex(2),
                onPress: () => parentNav()?.navigate('StudentMoreTab', { screen: 'Announcements' }),
            },
            {
                id: 'bell',
                title: 'Alerts',
                icon: 'notifications',
                color: tileColorForIndex(3),
                onPress: () => parentNav()?.navigate('StudentMoreTab', { screen: 'Notifications' }),
            },
            {
                id: 'more',
                title: 'More',
                icon: 'menu',
                color: tileColorForIndex(4),
                onPress: () => parentNav()?.navigate('StudentMoreTab', { screen: 'MoreMenu' }),
            },
        ],
        [navigation]
    );

    const greeting = useMemo(() => {
        const h = new Date().getHours();
        if (h < 12) return 'Good morning';
        if (h < 17) return 'Good afternoon';
        return 'Good evening';
    }, []);

    const roleLabel = getDashboardRoleLabel(user?.role);

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scroll}>
                <DashboardHero
                    greeting={greeting}
                    userName={user?.name || 'Student'}
                    roleLabel={roleLabel}
                    avatarUrl={user?.avatar}
                    showSettings={false}
                    onPressNotifications={() => parentNav()?.navigate('StudentMoreTab', { screen: 'Notifications' })}
                />

                <View style={styles.pad}>
                    <Text style={[styles.classTag, { color: textSub }]}>{studentInfo.className}</Text>

                    <View style={styles.kpiRow}>
                        <Card
                            style={{
                                ...styles.kpi,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Icon name="event-available" size={26} color={colors.success} />
                            <Text style={[styles.kpiVal, { color: textMain }]}>{studentInfo.attendance}%</Text>
                            <Text style={[styles.kpiLbl, { color: textSub }]}>Attendance</Text>
                        </Card>
                        <Card
                            style={{
                                ...styles.kpi,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Icon name="payment" size={26} color={studentInfo.feeBalance > 0 ? colors.error : colors.success} />
                            <Text
                                style={[
                                    styles.kpiVal,
                                    { color: studentInfo.feeBalance > 0 ? colors.error : colors.success },
                                ]}
                            >
                                {formatters.formatCurrency(studentInfo.feeBalance)}
                            </Text>
                            <Text style={[styles.kpiLbl, { color: textSub }]}>Fee balance</Text>
                        </Card>
                    </View>

                    <Card
                        style={{
                            ...styles.notice,
                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: isDark ? colors.borderDark : BRAND.border,
                        }}
                    >
                        <View style={styles.noticeRow}>
                            <Icon name="assignment-late" size={22} color={colors.warning} />
                            <Text style={[styles.noticeText, { color: textSub }]}>
                                {studentInfo.pendingAssignments} assignments due soon — open Homework when your school enables it.
                            </Text>
                        </View>
                    </Card>

                    <DashboardLineChart title="Attendance (sample weeks)" labels={['W1', 'W2', 'W3', 'W4']} data={[90, 92, 91, 93]} />
                    <DashboardBarChart title="Subjects — average % (sample)" labels={['Math', 'Eng', 'Sci']} data={[78, 85, 82]} />

                    <DashboardMenuGrid title="Go to" items={menuItems} />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { paddingBottom: SPACING.xxl },
    pad: { paddingHorizontal: SPACING.xl, paddingTop: SPACING.lg },
    classTag: { fontSize: FONT_SIZES.sm, fontWeight: '600', marginBottom: SPACING.md },
    kpiRow: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.md },
    kpi: {
        flex: 1,
        padding: SPACING.md,
        alignItems: 'center',
        gap: SPACING.xs,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
    },
    kpiVal: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    kpiLbl: { fontSize: FONT_SIZES.xs },
    notice: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.lg,
    },
    noticeRow: { flexDirection: 'row', alignItems: 'flex-start', gap: SPACING.sm },
    noticeText: { flex: 1, fontSize: FONT_SIZES.sm, lineHeight: 20 },
});
