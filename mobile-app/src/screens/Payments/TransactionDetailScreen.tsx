import React, { useCallback, useEffect, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    ActivityIndicator,
    Alert,
    Linking,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { financeApi } from '@api/finance.api';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { WEB_BASE_URL } from '@utils/env';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params?: { transactionId: number; transactionType: 'bank' | 'c2b' } };
}

export const TransactionDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const transactionId = route.params?.transactionId;
    const transactionType = route.params?.transactionType ?? 'bank';
    const { isDark, colors } = useTheme();
    const [detail, setDetail] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);
    const [acting, setActing] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const portalUrl = transactionId
        ? `${WEB_BASE_URL}/finance/bank-statements/${transactionId}?type=${transactionType}`
        : '';
    const transactionsIndexUrl = `${WEB_BASE_URL}/finance/bank-statements`;

    const load = useCallback(async () => {
        if (!transactionId) return;
        try {
            setLoading(true);
            const res = await financeApi.getFinanceTransaction(transactionId, transactionType);
            if (res.success && res.data) {
                setDetail(res.data);
            } else {
                setDetail(null);
            }
        } catch (e: any) {
            Alert.alert('Transaction', e?.message || 'Could not load');
            setDetail(null);
        } finally {
            setLoading(false);
        }
    }, [transactionId, transactionType]);

    useEffect(() => {
        if (!transactionId) {
            navigation.goBack();
            return;
        }
        load();
    }, [transactionId, load]);

    const markSwimming = async () => {
        if (!transactionId) return;
        try {
            setActing(true);
            const res = await financeApi.markTransactionsAsSwimming([transactionId]);
            if (res.success) {
                Alert.alert('Swimming', res.data?.message || 'Updated');
                load();
            } else {
                Alert.alert('Swimming', (res as any).message || 'Failed');
            }
        } catch (e: any) {
            Alert.alert('Swimming', e?.message || 'Failed');
        } finally {
            setActing(false);
        }
    };

    const openPortal = () => {
        if (portalUrl) {
            Linking.openURL(portalUrl);
        }
    };

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
                <View style={styles.headerRow}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={colors.primary} />
                    </TouchableOpacity>
                    <Text style={[styles.headerTitle, { color: textMain }]}>Transaction</Text>
                    <View style={{ width: 40 }} />
                </View>
                <ActivityIndicator style={{ marginTop: SPACING.xl }} color={colors.primary} />
            </SafeAreaView>
        );
    }

    const amount =
        typeof detail?.amount === 'number'
            ? detail.amount
            : typeof detail?.trans_amount === 'number'
              ? detail.trans_amount
              : null;

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <View style={styles.headerRow}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.headerTitle, { color: textMain }]} numberOfLines={1}>
                    {transactionType === 'bank' ? 'Bank line' : 'M-Pesa C2B'}
                </Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll}>
                <Card>
                    <Text style={[styles.amount, { color: colors.success }]}>
                        {amount != null ? formatters.formatCurrency(amount) : '—'}
                    </Text>
                    <Text style={[styles.line, { color: textMain }]}>
                        {String(detail?.reference_number ?? detail?.trans_id ?? `#${transactionId}`)}
                    </Text>
                    {detail?.description ? (
                        <Text style={[styles.lineSm, { color: textSub }]} numberOfLines={4}>
                            {String(detail.description)}
                        </Text>
                    ) : null}
                    {detail?.bill_ref_number ? (
                        <Text style={[styles.lineSm, { color: textSub }]}>Bill ref: {String(detail.bill_ref_number)}</Text>
                    ) : null}
                    {detail?.trans_time ? (
                        <Text style={[styles.lineSm, { color: textSub }]}>M-Pesa time: {String(detail.trans_time)}</Text>
                    ) : null}
                    <Text style={[styles.lineSm, { color: textSub }]}>
                        {String(detail?.status ?? '')} · {String(detail?.match_status ?? detail?.allocation_status ?? '')}
                    </Text>
                    {detail?.is_swimming_transaction ? (
                        <Text style={[styles.swim, { color: '#0d9488' }]}>Marked as swimming</Text>
                    ) : null}
                </Card>

                <Text style={[styles.hint, { color: textSub }]}>
                    Confirm, auto-assign batches, split, transfer, and full sibling allocation run in the web portal.
                    Use the buttons below for quick actions or open the full transaction page.
                </Text>

                <TouchableOpacity
                    style={[styles.btn, { backgroundColor: colors.primary }]}
                    onPress={openPortal}
                >
                    <Icon name="open-in-browser" size={20} color="#fff" />
                    <Text style={styles.btnText}>Open in portal</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={[styles.btn, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderWidth: 1, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                    onPress={() => Linking.openURL(transactionsIndexUrl)}
                >
                    <Icon name="list-alt" size={20} color={colors.primary} />
                    <Text style={[styles.btnTextOutline, { color: colors.primary }]}>All transactions (portal)</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={[styles.btn, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderWidth: 1, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                    onPress={markSwimming}
                    disabled={acting || !!detail?.is_swimming_transaction}
                >
                    <Icon name="pool" size={20} color={detail?.is_swimming_transaction ? textSub : colors.primary} />
                    <Text style={[styles.btnTextOutline, { color: detail?.is_swimming_transaction ? textSub : colors.primary }]}>
                        {detail?.is_swimming_transaction ? 'Already swimming' : 'Mark as swimming'}
                    </Text>
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    headerRow: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    backBtn: { padding: SPACING.sm },
    headerTitle: { flex: 1, fontSize: FONT_SIZES.lg, fontWeight: '700', textAlign: 'center' },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    amount: { fontSize: FONT_SIZES.xxl, fontWeight: '700' },
    line: { fontSize: FONT_SIZES.md, marginTop: SPACING.sm },
    lineSm: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    swim: { fontSize: FONT_SIZES.sm, marginTop: SPACING.sm, fontWeight: '600' },
    hint: { fontSize: FONT_SIZES.sm, marginTop: SPACING.lg, lineHeight: 20 },
    btn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: SPACING.sm,
        paddingVertical: SPACING.md,
        borderRadius: RADIUS.md,
        marginTop: SPACING.sm,
    },
    btnText: { color: '#fff', fontWeight: '600', fontSize: FONT_SIZES.md },
    btnTextOutline: { fontWeight: '600', fontSize: FONT_SIZES.md },
});
