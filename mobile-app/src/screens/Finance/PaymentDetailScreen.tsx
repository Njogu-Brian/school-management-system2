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
    Share,
    Linking,
} from 'react-native';
import { WebView } from 'react-native-webview';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { financeApi } from '@api/finance.api';
import { Payment } from '@types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES, COLORS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params?: { paymentId: number } };
}

export const PaymentDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const paymentId = route.params?.paymentId;
    const { isDark, colors } = useTheme();
    const [payment, setPayment] = useState<Payment | null>(null);
    const [loading, setLoading] = useState(true);
    const [showReceipt, setShowReceipt] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        if (!paymentId) return;
        try {
            setLoading(true);
            const res = await financeApi.getPayment(paymentId);
            if (res.success && res.data) {
                setPayment(res.data as Payment);
            } else {
                setPayment(null);
            }
        } catch (e: any) {
            Alert.alert('Payment', e?.message || 'Could not load payment');
            setPayment(null);
        } finally {
            setLoading(false);
        }
    }, [paymentId]);

    useEffect(() => {
        if (!paymentId) {
            Alert.alert('Error', 'Missing payment');
            navigation.goBack();
            return;
        }
        load();
    }, [paymentId, load]);

    const receiptUrl = payment?.receipt_public_url;

    const onShare = async () => {
        if (!receiptUrl) {
            Alert.alert('Receipt', 'No public receipt link is available yet.');
            return;
        }
        try {
            await Share.share({
                message: `Receipt ${payment?.receipt_number ?? ''}\n${receiptUrl}`,
                url: receiptUrl,
            });
        } catch {
            /* user cancelled */
        }
    };

    const onOpenBrowser = () => {
        if (receiptUrl) {
            Linking.openURL(receiptUrl);
        }
    };

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
                <View style={styles.headerRow}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={colors.primary} />
                    </TouchableOpacity>
                    <Text style={[styles.headerTitle, { color: textMain }]}>Payment</Text>
                    <View style={{ width: 40 }} />
                </View>
                <ActivityIndicator style={{ marginTop: SPACING.xl }} color={colors.primary} />
            </SafeAreaView>
        );
    }

    if (!payment) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={{ color: textSub, padding: SPACING.xl }}>Payment not found.</Text>
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: bg }]}>
            <View style={styles.headerRow}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.headerTitle, { color: textMain }]} numberOfLines={1}>
                    {payment.receipt_number}
                </Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
                <Card>
                    <Text style={[styles.amount, { color: colors.success }]}>{formatters.formatCurrency(payment.amount)}</Text>
                    <Text style={[styles.line, { color: textMain }]}>
                        {payment.student_name}
                        {payment.student_admission_number ? ` · ${payment.student_admission_number}` : ''}
                    </Text>
                    <Text style={[styles.lineSm, { color: textSub }]}>
                        {formatters.formatDate(payment.payment_date)} · {payment.payment_method}
                    </Text>
                    {payment.reference_number ? (
                        <Text style={[styles.lineSm, { color: textSub }]}>Ref: {payment.reference_number}</Text>
                    ) : null}
                    {payment.unallocated_amount != null && payment.unallocated_amount > 0.009 ? (
                        <Text style={[styles.lineSm, { color: COLORS.warning }]}>
                            Unallocated: {formatters.formatCurrency(payment.unallocated_amount)}
                        </Text>
                    ) : null}
                </Card>

                {payment.allocations && payment.allocations.length > 0 ? (
                    <View style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: textMain }]}>Allocated to invoices</Text>
                        {payment.allocations.map((a) => (
                            <View key={a.id} style={[styles.allocRow, { borderColor: isDark ? colors.borderDark : BRAND.border }]}>
                                <Text style={{ color: textMain, flex: 1 }}>{a.invoice_number || `Invoice #${a.invoice_id}`}</Text>
                                <Text style={{ color: textMain, fontWeight: '600' }}>{formatters.formatCurrency(a.amount)}</Text>
                            </View>
                        ))}
                    </View>
                ) : null}

                {payment.portal_note ? (
                    <Text style={[styles.portalNote, { color: textSub }]}>{payment.portal_note}</Text>
                ) : null}

                <View style={styles.actions}>
                    {receiptUrl ? (
                        <>
                            <TouchableOpacity
                                style={[styles.btn, { backgroundColor: colors.primary }]}
                                onPress={() => setShowReceipt(true)}
                            >
                                <Icon name="receipt" size={20} color={Palette.onPrimary} />
                                <Text style={styles.btnText}>View receipt</Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.btn, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderWidth: 1, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                                onPress={onOpenBrowser}
                            >
                                <Icon name="open-in-browser" size={20} color={colors.primary} />
                                <Text style={[styles.btnTextOutline, { color: colors.primary }]}>Open in browser</Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.btn, { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderWidth: 1, borderColor: isDark ? colors.borderDark : BRAND.border }]}
                                onPress={onShare}
                            >
                                <Icon name="share" size={20} color={colors.primary} />
                                <Text style={[styles.btnTextOutline, { color: colors.primary }]}>Share link</Text>
                            </TouchableOpacity>
                        </>
                    ) : (
                        <Text style={{ color: textSub, fontSize: FONT_SIZES.sm }}>
                            Receipt link will appear after the payment is saved with a public token.
                        </Text>
                    )}
                </View>
            </ScrollView>

            {showReceipt && receiptUrl ? (
                <View style={styles.webWrap}>
                    <View style={[styles.webHeader, { backgroundColor: bg }]}>
                        <TouchableOpacity onPress={() => setShowReceipt(false)} style={styles.backBtn}>
                            <Icon name="close" size={24} color={colors.primary} />
                        </TouchableOpacity>
                        <Text style={{ color: textMain, fontWeight: '600' }}>Receipt</Text>
                        <View style={{ width: 40 }} />
                    </View>
                    <WebView source={{ uri: receiptUrl }} style={styles.webview} />
                </View>
            ) : null}
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
    section: { marginTop: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '600', marginBottom: SPACING.sm },
    allocRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    portalNote: { fontSize: FONT_SIZES.sm, marginTop: SPACING.lg, lineHeight: 20 },
    actions: { marginTop: SPACING.lg, gap: SPACING.sm },
    btn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: SPACING.sm,
        paddingVertical: SPACING.md,
        borderRadius: RADIUS.md,
    },
    btnText: { color: Palette.onPrimary, fontWeight: '600', fontSize: FONT_SIZES.md },
    btnTextOutline: { fontWeight: '600', fontSize: FONT_SIZES.md },
    webWrap: {
        ...StyleSheet.absoluteFillObject,
        backgroundColor: Palette.modalSurfaceLight,
        zIndex: 10,
    },
    webHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingTop: SPACING.sm,
        paddingHorizontal: SPACING.sm,
    },
    webview: { flex: 1 },
});
