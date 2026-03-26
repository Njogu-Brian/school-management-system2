import React, { useEffect, useState, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    ActivityIndicator,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { StatusBadge } from '@components/common/StatusBadge';
import { financeApi } from '@api/finance.api';
import { Invoice } from '@types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void; navigate: (name: string, params?: object) => void };
    route: { params?: { invoiceId: number } };
}

const statusVariant = (status: string) => {
    switch (status) {
        case 'paid':
            return 'success';
        case 'partially_paid':
            return 'info';
        case 'overdue':
            return 'error';
        default:
            return 'default';
    }
};

export const InvoiceDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const invoiceId = route.params?.invoiceId;
    const { isDark, colors } = useTheme();
    const [invoice, setInvoice] = useState<Invoice | null>(null);
    const [loading, setLoading] = useState(true);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        if (!invoiceId) return;
        try {
            setLoading(true);
            const res = await financeApi.getInvoice(invoiceId);
            if (res.success && res.data) {
                setInvoice(res.data as Invoice);
            } else {
                setInvoice(null);
            }
        } catch (e: any) {
            Alert.alert('Invoice', e?.message || 'Could not load invoice');
            setInvoice(null);
        } finally {
            setLoading(false);
        }
    }, [invoiceId]);

    useEffect(() => {
        if (!invoiceId) {
            Alert.alert('Error', 'Missing invoice');
            navigation.goBack();
            return;
        }
        load();
    }, [invoiceId, load]);

    if (loading) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <View style={[styles.header, { borderBottomColor: isDark ? colors.borderDark : BRAND.border }]}>
                    <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                        <Icon name="arrow-back" size={24} color={textMain} />
                    </TouchableOpacity>
                    <Text style={[styles.headerTitle, { color: textMain }]}>Invoice</Text>
                    <View style={{ width: 24 }} />
                </View>
                <ActivityIndicator style={{ marginTop: SPACING.xxl }} color={BRAND.primary} />
            </SafeAreaView>
        );
    }

    if (!invoice) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <View style={[styles.header, { borderBottomColor: isDark ? colors.borderDark : BRAND.border }]}>
                    <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                        <Icon name="arrow-back" size={24} color={textMain} />
                    </TouchableOpacity>
                    <Text style={[styles.headerTitle, { color: textMain }]}>Invoice</Text>
                    <View style={{ width: 24 }} />
                </View>
                <Text style={[styles.meta, { color: textSub, textAlign: 'center', marginTop: SPACING.xl }]}>
                    Could not load this invoice.
                </Text>
            </SafeAreaView>
        );
    }

    const ext = invoice as Invoice & {
        term_name?: string | null;
        academic_year_name?: string | null;
        notes?: string | null;
    };

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <View style={[styles.header, { borderBottomColor: isDark ? colors.borderDark : BRAND.border }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                    <Icon name="arrow-back" size={24} color={textMain} />
                </TouchableOpacity>
                <Text style={[styles.headerTitle, { color: textMain }]} numberOfLines={1}>
                    #{invoice.invoice_number}
                </Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
                <Card style={{ borderRadius: RADIUS.card, borderColor: BRAND.border }}>
                    <View style={styles.rowBetween}>
                        <Text style={[styles.studentName, { color: textMain }]}>{invoice.student_name}</Text>
                        <StatusBadge status={invoice.status} variant={statusVariant(invoice.status)} />
                    </View>
                    <Text style={[styles.meta, { color: textSub }]}>{invoice.student_admission_number}</Text>
                    {(ext.term_name || ext.academic_year_name) && (
                        <Text style={[styles.meta, { color: textSub }]}>
                            {[ext.academic_year_name, ext.term_name].filter(Boolean).join(' · ')}
                        </Text>
                    )}
                </Card>

                <Card style={{ borderRadius: RADIUS.card, borderColor: BRAND.border }}>
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Amounts</Text>
                    <Row label="Total" value={formatters.formatCurrency(invoice.total_amount)} valueColor={textMain} />
                    <Row label="Paid" value={formatters.formatCurrency(invoice.paid_amount)} valueColor={colors.success} />
                    <Row
                        label="Balance"
                        value={formatters.formatCurrency(invoice.balance)}
                        valueColor={invoice.balance > 0 ? colors.error : colors.success}
                    />
                    <Row label="Due" value={invoice.due_date ? formatters.formatDate(invoice.due_date) : '—'} valueColor={textSub} />
                    <Row label="Issued" value={formatters.formatDate(invoice.issue_date)} valueColor={textSub} />
                </Card>

                {invoice.items && invoice.items.length > 0 && (
                    <Card style={{ borderRadius: RADIUS.card, borderColor: BRAND.border }}>
                        <Text style={[styles.sectionTitle, { color: textMain }]}>Line items</Text>
                        {invoice.items.map((line) => (
                            <View
                                key={line.id}
                                style={[styles.lineRow, { borderBottomColor: isDark ? colors.borderDark : BRAND.border }]}
                            >
                                <Text style={[styles.lineName, { color: textMain }]}>{line.votehead_name}</Text>
                                <Text style={[styles.lineAmt, { color: textMain }]}>
                                    {formatters.formatCurrency(line.total ?? line.amount)}
                                </Text>
                            </View>
                        ))}
                    </Card>
                )}

                {ext.notes ? (
                    <Card style={{ borderRadius: RADIUS.card, borderColor: BRAND.border }}>
                        <Text style={[styles.sectionTitle, { color: textMain }]}>Notes</Text>
                        <Text style={{ color: textSub }}>{ext.notes}</Text>
                    </Card>
                ) : null}

                {invoice.student_id ? (
                    <TouchableOpacity
                        style={[styles.linkBtn, { borderColor: BRAND.primary }]}
                        onPress={() =>
                            navigation.navigate('RecordPayment', {
                                studentId: invoice.student_id,
                            })
                        }
                    >
                        <Icon name="payment" size={20} color={BRAND.primary} />
                        <Text style={{ color: BRAND.primary, fontWeight: '600', marginLeft: SPACING.sm }}>
                            Record payment
                        </Text>
                    </TouchableOpacity>
                ) : null}
            </ScrollView>
        </SafeAreaView>
    );
};

const Row: React.FC<{
    label: string;
    value: string;
    valueColor: string;
}> = ({ label, value, valueColor }) => (
    <View style={styles.amountRow}>
        <Text style={[styles.amountLabel, { color: BRAND.muted }]}>{label}</Text>
        <Text style={[styles.amountValue, { color: valueColor }]}>{value}</Text>
    </View>
);

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    headerTitle: { flex: 1, textAlign: 'center', fontSize: FONT_SIZES.lg, fontWeight: '700' },
    scroll: { padding: SPACING.lg, paddingBottom: SPACING.xxl },
    rowBetween: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        gap: SPACING.md,
    },
    studentName: { fontSize: FONT_SIZES.lg, fontWeight: '700', flex: 1 },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.md },
    amountRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: SPACING.sm,
    },
    amountLabel: { fontSize: FONT_SIZES.sm },
    amountValue: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    lineRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    lineName: { flex: 1, fontSize: FONT_SIZES.sm },
    lineAmt: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    linkBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        padding: SPACING.md,
        borderRadius: RADIUS.button,
        borderWidth: 1,
        marginTop: SPACING.md,
    },
});
