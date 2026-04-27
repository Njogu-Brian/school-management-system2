import React, { useEffect, useState } from 'react';
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
import { financeApi } from '@api/finance.api';
import { StudentStatement } from 'types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params?: { studentId: number; studentName?: string } };
}

export const StudentStatementScreen: React.FC<Props> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const studentId = route.params?.studentId;
    const studentName = route.params?.studentName;
    const [year, setYear] = useState(new Date().getFullYear());
    const [loading, setLoading] = useState(true);
    const [statement, setStatement] = useState<StudentStatement | null>(null);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    useEffect(() => {
        if (!studentId) {
            Alert.alert('Error', 'Missing student');
            navigation.goBack();
            return;
        }
        load();
    }, [studentId, year]);

    const load = async () => {
        const id = studentId;
        if (id == null) return;
        try {
            setLoading(true);
            const res = await financeApi.getStudentStatement(id, year);
            if (res.success && res.data) {
                setStatement(res.data as StudentStatement);
            } else {
                setStatement(null);
            }
        } catch (e: any) {
            Alert.alert('Statement', e?.message || 'Could not load fee statement');
            setStatement(null);
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <View style={[styles.header, { borderBottomColor: isDark ? colors.borderDark : BRAND.border }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                    <Icon name="arrow-back" size={24} color={textMain} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: textMain }]}>Fee statement</Text>
                <View style={{ width: 24 }} />
            </View>

            <View style={styles.yearRow}>
                <TouchableOpacity
                    style={[styles.yearBtn, { backgroundColor: surface, borderColor: BRAND.border }]}
                    onPress={() => setYear((y) => y - 1)}
                >
                    <Icon name="chevron-left" size={22} color={BRAND.primary} />
                </TouchableOpacity>
                <Text style={[styles.yearText, { color: textMain }]}>{year}</Text>
                <TouchableOpacity
                    style={[styles.yearBtn, { backgroundColor: surface, borderColor: BRAND.border }]}
                    onPress={() => setYear((y) => y + 1)}
                    disabled={year >= new Date().getFullYear()}
                >
                    <Icon name="chevron-right" size={22} color={BRAND.primary} />
                </TouchableOpacity>
            </View>

            {loading ? (
                <View style={styles.centered}>
                    <ActivityIndicator size="large" color={BRAND.primary} />
                </View>
            ) : !statement ? (
                <View style={styles.centered}>
                    <Text style={{ color: textSub }}>No data</Text>
                </View>
            ) : (
                <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
                    <Card style={[styles.hero, { backgroundColor: surface, borderRadius: RADIUS.card }]}>
                        <Text style={[styles.studentLabel, { color: textSub }]}>Student</Text>
                        <Text style={[styles.studentName, { color: textMain }]}>
                            {statement.student?.full_name || studentName || '—'}
                        </Text>
                        <Text style={[styles.meta, { color: textSub }]}>
                            {statement.student?.admission_number} · {statement.student?.class_name}
                        </Text>
                        <View style={styles.totalsRow}>
                            <View style={styles.totalCell}>
                                <Text style={[styles.totalLabel, { color: textSub }]}>Invoiced</Text>
                                <Text style={[styles.totalVal, { color: textMain }]}>
                                    {formatters.formatCurrency(statement.total_invoiced)}
                                </Text>
                            </View>
                            <View style={styles.totalCell}>
                                <Text style={[styles.totalLabel, { color: textSub }]}>Paid</Text>
                                <Text style={[styles.totalVal, { color: BRAND.success }]}>
                                    {formatters.formatCurrency(statement.total_paid)}
                                </Text>
                            </View>
                            <View style={styles.totalCell}>
                                <Text style={[styles.totalLabel, { color: textSub }]}>Balance</Text>
                                <Text
                                    style={[
                                        styles.totalVal,
                                        { color: statement.closing_balance > 0 ? BRAND.danger : BRAND.success },
                                    ]}
                                >
                                    {formatters.formatCurrency(statement.closing_balance)}
                                </Text>
                            </View>
                        </View>
                    </Card>

                    <Text style={[styles.sectionTitle, { color: textMain }]}>Activity</Text>
                    {statement.transactions?.map((row) => (
                        <View
                            key={`${row.type}-${row.id}`}
                            style={[
                                styles.txRow,
                                {
                                    backgroundColor: surface,
                                    borderColor: isDark ? colors.borderDark : BRAND.border,
                                    borderRadius: RADIUS.card,
                                },
                            ]}
                        >
                            <View style={styles.txLeft}>
                                <Text style={[styles.txDate, { color: textSub }]}>{row.date}</Text>
                                <Text style={[styles.txDesc, { color: textMain }]} numberOfLines={2}>
                                    {row.description}
                                </Text>
                                <Text style={[styles.txRef, { color: textSub }]}>{row.reference}</Text>
                            </View>
                            <View style={styles.txRight}>
                                {row.debit > 0 && (
                                    <Text style={{ color: BRAND.danger, fontWeight: '700' }}>
                                        +{formatters.formatCurrency(row.debit)}
                                    </Text>
                                )}
                                {row.credit > 0 && (
                                    <Text style={{ color: BRAND.success, fontWeight: '700' }}>
                                        −{formatters.formatCurrency(row.credit)}
                                    </Text>
                                )}
                                <Text style={[styles.bal, { color: textSub }]}>
                                    Bal {formatters.formatCurrency(row.balance)}
                                </Text>
                            </View>
                        </View>
                    ))}
                </ScrollView>
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.md,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    title: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    yearRow: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: SPACING.lg,
        paddingVertical: SPACING.md,
    },
    yearBtn: {
        width: 44,
        height: 44,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        alignItems: 'center',
        justifyContent: 'center',
    },
    yearText: { fontSize: FONT_SIZES.xl, fontWeight: '700', minWidth: 72, textAlign: 'center' },
    centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    scroll: { padding: SPACING.lg, paddingBottom: SPACING.xxl },
    hero: { padding: SPACING.lg, marginBottom: SPACING.md },
    studentLabel: { fontSize: FONT_SIZES.xs, textTransform: 'uppercase', letterSpacing: 0.5 },
    studentName: { fontSize: FONT_SIZES.xl, fontWeight: '700', marginTop: 4 },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    totalsRow: { flexDirection: 'row', marginTop: SPACING.lg, justifyContent: 'space-between' },
    totalCell: { flex: 1 },
    totalLabel: { fontSize: FONT_SIZES.xs },
    totalVal: { fontSize: FONT_SIZES.md, fontWeight: '700', marginTop: 4 },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.sm },
    txRow: {
        flexDirection: 'row',
        padding: SPACING.md,
        marginBottom: SPACING.sm,
        borderWidth: 1,
        justifyContent: 'space-between',
        gap: SPACING.sm,
    },
    txLeft: { flex: 1 },
    txRight: { alignItems: 'flex-end' },
    txDate: { fontSize: FONT_SIZES.xs },
    txDesc: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    txRef: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    bal: { fontSize: FONT_SIZES.xs, marginTop: 4 },
});
