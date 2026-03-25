import React, { useCallback, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { dashboardApi } from '@api/dashboard.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { formatters } from '@utils/formatters';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const ParentDashboardScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [childrenCount, setChildrenCount] = useState<number | null>(null);
    const [balance, setBalance] = useState<number | null>(null);
    const [refreshing, setRefreshing] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        try {
            const res = await dashboardApi.getStats();
            if (res.success && res.data) {
                setChildrenCount(res.data.children_count ?? null);
                setBalance(
                    typeof res.data.total_fee_balance === 'number' ? res.data.total_fee_balance : null
                );
            }
        } catch {
            setChildrenCount(null);
            setBalance(null);
        } finally {
            setRefreshing(false);
        }
    }, []);

    React.useEffect(() => {
        load();
    }, [load]);

    const onRefresh = () => {
        setRefreshing(true);
        load();
    };

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <ScrollView
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />
                }
            >
                <Text style={[styles.greet, { color: textSub }]}>Welcome back</Text>
                <Text style={[styles.name, { color: textMain }]}>{user?.name || 'Parent'}</Text>

                <View style={styles.row}>
                    <Card style={[styles.stat, { borderRadius: RADIUS.card }]}>
                        <Icon name="child-care" size={28} color={colors.primary} />
                        <Text style={[styles.statVal, { color: textMain }]}>
                            {childrenCount != null ? childrenCount : '—'}
                        </Text>
                        <Text style={[styles.statLabel, { color: textSub }]}>Children</Text>
                    </Card>
                    <Card style={[styles.stat, { borderRadius: RADIUS.card }]}>
                        <Icon name="account-balance" size={28} color="#ef4444" />
                        <Text style={[styles.statVal, { color: textMain }]} numberOfLines={1}>
                            {balance != null ? formatters.formatCurrency(balance) : '—'}
                        </Text>
                        <Text style={[styles.statLabel, { color: textSub }]}>Total balance</Text>
                    </Card>
                </View>

                <Text style={[styles.section, { color: textMain }]}>Quick links</Text>
                <TouchableOpacity
                    style={[styles.link, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                    onPress={() =>
                        navigation.getParent()?.navigate('ParentChildrenTab', { screen: 'ChildrenList' })
                    }
                >
                    <Icon name="school" size={22} color={colors.primary} />
                    <Text style={[styles.linkText, { color: textMain }]}>View children</Text>
                    <Icon name="chevron-right" size={22} color={textSub} />
                </TouchableOpacity>
                <TouchableOpacity
                    style={[styles.link, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                    onPress={() =>
                        navigation.getParent()?.navigate('ParentPaymentsTab', { screen: 'ParentPaymentsMain' })
                    }
                >
                    <Icon name="payment" size={22} color={colors.primary} />
                    <Text style={[styles.linkText, { color: textMain }]}>Fees & payments</Text>
                    <Icon name="chevron-right" size={22} color={textSub} />
                </TouchableOpacity>
                <TouchableOpacity
                    style={[styles.link, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                    onPress={() =>
                        navigation.getParent()?.navigate('ParentMoreTab', { screen: 'Announcements' })
                    }
                >
                    <Icon name="campaign" size={22} color={colors.primary} />
                    <Text style={[styles.linkText, { color: textMain }]}>Announcements</Text>
                    <Icon name="chevron-right" size={22} color={textSub} />
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    greet: { fontSize: FONT_SIZES.sm },
    name: { fontSize: FONT_SIZES.xxl, fontWeight: '700', marginBottom: SPACING.lg },
    row: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.xl },
    stat: { flex: 1, padding: SPACING.md, alignItems: 'center', gap: SPACING.xs },
    statVal: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    statLabel: { fontSize: FONT_SIZES.xs },
    section: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.sm },
    link: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: RADIUS.md,
        borderWidth: 1,
        marginBottom: SPACING.sm,
        gap: SPACING.md,
    },
    linkText: { flex: 1, fontSize: FONT_SIZES.md, fontWeight: '600' },
});
