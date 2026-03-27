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
    Modal,
    TextInput,
    KeyboardAvoidingView,
    Platform,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { financeApi } from '@api/finance.api';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import { WEB_BASE_URL } from '@utils/env';
import { canUseMpesaFinanceTools } from '@utils/financeRoles';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params?: { transactionId: number; transactionType: 'bank' | 'c2b' } };
}

type ShareRow = { student_id: string; amount: string };

function buildShareRows(
    d: Record<string, unknown> | null,
    txAmount: number | null
): ShareRow[] {
    const raw = d?.shared_allocations;
    if (Array.isArray(raw) && raw.length) {
        return raw.map((a: { student_id?: number; amount?: number }) => ({
            student_id: String(a.student_id ?? ''),
            amount: a.amount != null ? String(a.amount) : '',
        }));
    }
    if (d?.student_id) {
        return [{ student_id: String(d.student_id), amount: txAmount != null ? String(txAmount) : '' }];
    }
    return [{ student_id: '', amount: txAmount != null ? String(txAmount) : '' }];
}

export const TransactionDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const transactionId = route.params?.transactionId;
    const transactionType = route.params?.transactionType ?? 'bank';
    const { user } = useAuth();
    const { isDark, colors } = useTheme();
    const [detail, setDetail] = useState<Record<string, unknown> | null>(null);
    const [loading, setLoading] = useState(true);
    const [acting, setActing] = useState(false);
    const [shareOpen, setShareOpen] = useState(false);
    const [shareRows, setShareRows] = useState<ShareRow[]>([{ student_id: '', amount: '' }]);

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

    const canFinance = canUseMpesaFinanceTools(user);
    const canConfirm =
        canFinance && !!detail && (!!detail.student_id || !!detail.is_shared);

    const openShareModal = () => {
        const amt =
            typeof detail?.amount === 'number'
                ? detail.amount
                : typeof detail?.trans_amount === 'number'
                  ? detail.trans_amount
                  : null;
        setShareRows(buildShareRows(detail, amt));
        setShareOpen(true);
    };

    const confirmTransaction = () => {
        if (!transactionId) return;
        Alert.alert('Confirm transaction', 'Create payment and mark this transaction as collected?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Confirm',
                onPress: async () => {
                    try {
                        setActing(true);
                        const res = await financeApi.confirmFinanceTransaction(transactionId, transactionType);
                        if (res.success) {
                            Alert.alert('Confirmed', res.message || (res.data as { message?: string })?.message || 'Done');
                            load();
                        } else {
                            Alert.alert('Confirm', res.message || 'Failed');
                        }
                    } catch (e: unknown) {
                        const err = e as { message?: string };
                        Alert.alert('Confirm', err?.message || 'Failed');
                    } finally {
                        setActing(false);
                    }
                },
            },
        ]);
    };

    const rejectTransaction = () => {
        if (!transactionId) return;
        Alert.alert(
            'Reject transaction',
            'This will reverse related payments and reset the transaction to unassigned. Continue?',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Reject',
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            setActing(true);
                            const res = await financeApi.rejectFinanceTransaction(transactionId, transactionType);
                            if (res.success) {
                                Alert.alert('Rejected', res.message || (res.data as { message?: string })?.message || 'Done');
                                load();
                            } else {
                                Alert.alert('Reject', res.message || 'Failed');
                            }
                        } catch (e: unknown) {
                            const err = e as { message?: string };
                            Alert.alert('Reject', err?.message || 'Failed');
                        } finally {
                            setActing(false);
                        }
                    },
                },
            ]
        );
    };

    const submitShare = async () => {
        if (!transactionId) return;
        const allocations = shareRows
            .map((r) => ({
                student_id: parseInt(r.student_id, 10),
                amount: parseFloat(String(r.amount).replace(/,/g, '')),
            }))
            .filter((a) => a.student_id > 0 && !Number.isNaN(a.amount) && a.amount > 0);
        if (!allocations.length) {
            Alert.alert('Share', 'Enter at least one valid student ID and amount greater than 0.');
            return;
        }
        const txAmt =
            typeof detail?.amount === 'number'
                ? detail.amount
                : typeof detail?.trans_amount === 'number'
                  ? detail.trans_amount
                  : null;
        const total = allocations.reduce((s, a) => s + a.amount, 0);
        if (txAmt != null && total - txAmt > 0.01) {
            Alert.alert('Share', 'Total allocation cannot exceed the transaction amount.');
            return;
        }
        try {
            setActing(true);
            const res = await financeApi.shareFinanceTransaction(transactionId, allocations, transactionType);
            if (res.success) {
                Alert.alert('Shared', res.message || (res.data as { message?: string })?.message || 'Done');
                setShareOpen(false);
                load();
            } else {
                Alert.alert('Share', res.message || 'Failed');
            }
        } catch (e: unknown) {
            const err = e as { message?: string };
            Alert.alert('Share', err?.message || 'Failed');
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
                        <Text style={[styles.swim, { color: colors.primary }]}>Marked as swimming</Text>
                    ) : null}
                </Card>

                <Text style={[styles.hint, { color: textSub }]}>
                    Finance staff can confirm, reject, or split among siblings here. Other advanced actions remain in the
                    web portal.
                </Text>

                {canFinance ? (
                    <>
                        <TouchableOpacity
                            style={[
                                styles.btn,
                                {
                                    backgroundColor: colors.success,
                                    opacity: canConfirm && !acting ? 1 : 0.45,
                                },
                            ]}
                            onPress={confirmTransaction}
                            disabled={!canConfirm || acting}
                        >
                            <Icon name="check-circle" size={20} color={Palette.onPrimary} />
                            <Text style={styles.btnText}>Confirm (collect)</Text>
                        </TouchableOpacity>

                        <TouchableOpacity
                            style={[styles.btn, { backgroundColor: Palette.destructive }]}
                            onPress={rejectTransaction}
                            disabled={acting}
                        >
                            <Icon name="cancel" size={20} color={Palette.onPrimary} />
                            <Text style={styles.btnText}>Reject (reset)</Text>
                        </TouchableOpacity>

                        <TouchableOpacity
                            style={[
                                styles.btn,
                                {
                                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                    borderWidth: 1,
                                    borderColor: isDark ? colors.borderDark : BRAND.border,
                                },
                            ]}
                            onPress={openShareModal}
                            disabled={acting}
                        >
                            <Icon name="people" size={20} color={colors.primary} />
                            <Text style={[styles.btnTextOutline, { color: colors.primary }]}>Share among siblings</Text>
                        </TouchableOpacity>
                    </>
                ) : null}

                <TouchableOpacity
                    style={[styles.btn, { backgroundColor: colors.primary }]}
                    onPress={openPortal}
                >
                    <Icon name="open-in-browser" size={20} color={Palette.onPrimary} />
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

            <Modal visible={shareOpen} animationType="slide" transparent onRequestClose={() => setShareOpen(false)}>
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    style={styles.modalBackdrop}
                >
                    <View style={[styles.modalCard, { backgroundColor: isDark ? colors.surfaceDark : Palette.modalSurfaceLight }]}>
                        <Text style={[styles.modalTitle, { color: textMain }]}>Share among siblings</Text>
                        <Text style={[styles.modalHint, { color: textSub }]}>
                            Student IDs and amounts (Ksh). Total must not exceed the transaction amount.
                        </Text>
                        {shareRows.map((row, i) => (
                            <View key={i} style={styles.shareRow}>
                                <TextInput
                                    style={[
                                        styles.input,
                                        { color: textMain, borderColor: isDark ? colors.borderDark : BRAND.border },
                                    ]}
                                    placeholder="Student ID"
                                    placeholderTextColor={textSub}
                                    keyboardType="number-pad"
                                    value={row.student_id}
                                    onChangeText={(t) => {
                                        const next = [...shareRows];
                                        next[i] = { ...next[i], student_id: t };
                                        setShareRows(next);
                                    }}
                                />
                                <TextInput
                                    style={[
                                        styles.input,
                                        styles.inputAmt,
                                        { color: textMain, borderColor: isDark ? colors.borderDark : BRAND.border },
                                    ]}
                                    placeholder="Amount"
                                    placeholderTextColor={textSub}
                                    keyboardType="decimal-pad"
                                    value={row.amount}
                                    onChangeText={(t) => {
                                        const next = [...shareRows];
                                        next[i] = { ...next[i], amount: t };
                                        setShareRows(next);
                                    }}
                                />
                                {shareRows.length > 1 ? (
                                    <TouchableOpacity
                                        onPress={() => setShareRows(shareRows.filter((_, j) => j !== i))}
                                        hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                                    >
                                        <Icon name="remove-circle" size={28} color={Palette.destructive} />
                                    </TouchableOpacity>
                                ) : (
                                    <View style={{ width: 28 }} />
                                )}
                            </View>
                        ))}
                        <TouchableOpacity
                            style={styles.addRow}
                            onPress={() => setShareRows([...shareRows, { student_id: '', amount: '' }])}
                        >
                            <Icon name="add-circle-outline" size={22} color={colors.primary} />
                            <Text style={{ color: colors.primary, fontWeight: '600' }}>Add sibling row</Text>
                        </TouchableOpacity>
                        <View style={styles.modalActions}>
                            <TouchableOpacity style={styles.modalBtnCancel} onPress={() => setShareOpen(false)}>
                                <Text style={{ color: textSub, fontWeight: '600' }}>Cancel</Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.modalBtnOk, { backgroundColor: colors.primary }]}
                                onPress={submitShare}
                                disabled={acting}
                            >
                                <Text style={styles.btnText}>Apply share</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </KeyboardAvoidingView>
            </Modal>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { padding: SPACING.xl, paddingTop: SPACING.md, paddingBottom: SPACING.xxl },
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
        borderRadius: RADIUS.button,
        marginTop: SPACING.sm,
    },
    btnText: { color: Palette.onPrimary, fontWeight: '600', fontSize: FONT_SIZES.md },
    btnTextOutline: { fontWeight: '600', fontSize: FONT_SIZES.md },
    modalBackdrop: {
        flex: 1,
        justifyContent: 'flex-end',
        backgroundColor: 'rgba(0,0,0,0.45)',
    },
    modalCard: {
        padding: SPACING.lg,
        paddingBottom: SPACING.xl,
        borderTopLeftRadius: RADIUS.card,
        borderTopRightRadius: RADIUS.card,
        maxHeight: '85%',
    },
    modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', marginBottom: SPACING.xs },
    modalHint: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md, lineHeight: 20 },
    shareRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        marginBottom: SPACING.sm,
    },
    input: {
        flex: 1,
        borderWidth: 1,
        borderRadius: RADIUS.button,
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.sm,
        fontSize: FONT_SIZES.md,
    },
    inputAmt: { flex: 0.85 },
    addRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.xs,
        marginTop: SPACING.xs,
        marginBottom: SPACING.md,
    },
    modalActions: {
        flexDirection: 'row',
        justifyContent: 'flex-end',
        alignItems: 'center',
        gap: SPACING.md,
        marginTop: SPACING.sm,
    },
    modalBtnCancel: { paddingVertical: SPACING.sm, paddingHorizontal: SPACING.md },
    modalBtnOk: {
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.lg,
        borderRadius: RADIUS.button,
    },
});
