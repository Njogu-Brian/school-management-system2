import React, { useCallback, useEffect, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    FlatList,
    RefreshControl,
    TouchableOpacity,
    Alert,
    Linking,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { studentsApi } from '@api/students.api';
import { financeApi } from '@api/finance.api';
import { Student } from '@types/student.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const ParentPaymentsScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [rows, setRows] = useState<{ student: Student; balance: number }[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        try {
            const listRes = await studentsApi.getStudents({ per_page: 100 });
            if (!listRes.success || !listRes.data?.data) {
                setRows([]);
                return;
            }
            const students = listRes.data.data;
            const next: { student: Student; balance: number }[] = [];
            for (const s of students) {
                try {
                    const st = await studentsApi.getStudentStats(s.id);
                    const bal = st.success && st.data?.fees_balance != null ? Number(st.data.fees_balance) : 0;
                    next.push({ student: s, balance: bal });
                } catch {
                    next.push({ student: s, balance: 0 });
                }
            }
            setRows(next);
        } catch (e: any) {
            Alert.alert('Fees', e?.message || 'Could not load');
            setRows([]);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const openPay = async (studentId: number) => {
        try {
            const res = await financeApi.getMpesaPaymentLink(studentId);
            if (res.success && res.data?.url) {
                Alert.alert('Pay fees', 'Open the school payment page?', [
                    { text: 'Open', onPress: () => Linking.openURL(res.data!.url) },
                    { text: 'Cancel', style: 'cancel' },
                ]);
            }
        } catch (e: any) {
            Alert.alert('Payment link', e?.message || 'Unavailable');
        }
    };

    if (loading) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <LoadingState message="Loading balances..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <Text style={[styles.title, { color: textMain }]}>Fees & pay</Text>
            <Text style={[styles.sub, { color: textSub }]}>
                Outstanding per child. Tap Pay to open the school payment page in the browser.
            </Text>
            <FlatList
                data={rows}
                keyExtractor={(item) => String(item.student.id)}
                contentContainerStyle={styles.list}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} colors={[colors.primary]} />
                }
                renderItem={({ item }) => (
                    <Card style={{ borderRadius: RADIUS.card }}>
                        <View style={styles.row}>
                            <View style={{ flex: 1 }}>
                                <Text style={[styles.name, { color: textMain }]}>{item.student.full_name}</Text>
                                <Text style={[styles.meta, { color: textSub }]}>{item.student.admission_number}</Text>
                                <Text style={[styles.bal, { color: item.balance > 0 ? colors.error : colors.success }]}>
                                    {formatters.formatCurrency(item.balance)}
                                </Text>
                            </View>
                            <View style={styles.actions}>
                                <TouchableOpacity
                                    style={[styles.iconBtn, { backgroundColor: colors.primary + '18' }]}
                                    onPress={() =>
                                        navigation.navigate('StudentDetail', { studentId: item.student.id })
                                    }
                                >
                                    <Icon name="person" color={colors.primary} size={22} />
                                </TouchableOpacity>
                                <TouchableOpacity
                                    style={[styles.iconBtn, { backgroundColor: `${colors.primary}22` }]}
                                    onPress={() => openPay(item.student.id)}
                                >
                                    <Icon name="open-in-browser" color={colors.primary} size={22} />
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Card>
                )}
                ListEmptyComponent={<EmptyState icon="payment" title="No children" message="Link your account in the portal if this is empty." />}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '700', paddingHorizontal: SPACING.xl, marginTop: SPACING.sm },
    sub: { fontSize: FONT_SIZES.xs, paddingHorizontal: SPACING.xl, marginBottom: SPACING.md, marginTop: 4 },
    list: { padding: SPACING.xl, gap: SPACING.sm, paddingBottom: SPACING.xxl },
    row: { flexDirection: 'row', alignItems: 'center' },
    name: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    meta: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    bal: { fontSize: FONT_SIZES.md, fontWeight: '700', marginTop: 6 },
    actions: { flexDirection: 'row', gap: SPACING.sm },
    iconBtn: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center' },
});
