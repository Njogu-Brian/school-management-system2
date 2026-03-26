import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Alert,
    ActivityIndicator,
    Linking,
    Share,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { UserRole } from '@constants/roles';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { StatusBadge } from '@components/common/StatusBadge';
import { studentsApi } from '@api/students.api';
import { financeApi } from '@api/finance.api';
import { Student } from '@types/student.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { MpesaPromptModal } from '@components/MpesaPromptModal';
import { canUseMpesaFinanceTools } from '@utils/financeRoles';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StudentDetailScreenProps {
    navigation: any;
    route: any;
}

type CalDay = { date: string; status: string; is_excused: boolean };

const MANAGER_ROLES = [UserRole.SUPER_ADMIN, UserRole.ADMIN, UserRole.SECRETARY];

export const StudentDetailScreen: React.FC<StudentDetailScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const { studentId } = route.params;
    const canManageStudents = !!(user && MANAGER_ROLES.includes(user.role));
    const canFinanceMpesa = canUseMpesaFinanceTools(user);
    const isParentUser = !!(
        user && (user.role === UserRole.PARENT || user.role === UserRole.GUARDIAN)
    );
    const [mpesaOpen, setMpesaOpen] = useState(false);

    const [student, setStudent] = useState<Student | null>(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('overview');
    const [stats, setStats] = useState<{
        attendance_percentage: number | null;
        fees_balance: number;
        attendance_days_marked?: number;
    } | null>(null);
    const [calYear, setCalYear] = useState(new Date().getFullYear());
    const [calMonth, setCalMonth] = useState(new Date().getMonth() + 1);
    const [calRows, setCalRows] = useState<CalDay[]>([]);
    const [loadingCal, setLoadingCal] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;

    const loadStudent = useCallback(async () => {
        try {
            setLoading(true);
            const response = await studentsApi.getStudent(studentId);
            if (response.success && response.data) {
                setStudent(response.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load student');
        } finally {
            setLoading(false);
        }
    }, [studentId]);

    const loadStats = useCallback(async () => {
        try {
            const res = await studentsApi.getStudentStats(studentId);
            if (res.success && res.data) {
                setStats({
                    attendance_percentage: res.data.attendance_percentage,
                    fees_balance: res.data.fees_balance,
                    attendance_days_marked: res.data.attendance_days_marked,
                });
            }
        } catch {
            setStats(null);
        }
    }, [studentId]);

    const loadCalendar = useCallback(async () => {
        try {
            setLoadingCal(true);
            const res = await studentsApi.getAttendanceCalendar(studentId, calYear, calMonth);
            if (res.success && res.data) {
                setCalRows(res.data);
            } else {
                setCalRows([]);
            }
        } catch {
            setCalRows([]);
        } finally {
            setLoadingCal(false);
        }
    }, [studentId, calYear, calMonth]);

    useEffect(() => {
        loadStudent();
    }, [loadStudent]);

    useEffect(() => {
        if (student) {
            loadStats();
        }
    }, [student, loadStats]);

    useEffect(() => {
        if (activeTab === 'attendance' && student) {
            loadCalendar();
        }
    }, [activeTab, student, loadCalendar]);

    const tabs = [
        { key: 'overview', label: 'Info', icon: 'info' },
        { key: 'attendance', label: 'Attendance', icon: 'event' },
        { key: 'academics', label: 'Academics', icon: 'school' },
        { key: 'finance', label: 'Finance', icon: 'payments' },
    ];

    const openPaymentLinkForParent = async () => {
        try {
            const res = await financeApi.getMpesaPaymentLink(studentId);
            if (res.success && res.data?.url) {
                const url = res.data.url;
                Alert.alert('Payment link', url, [
                    { text: 'Open', onPress: () => Linking.openURL(url) },
                    { text: 'Share', onPress: () => Share.share({ message: url }) },
                    { text: 'Cancel', style: 'cancel' },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Could not get payment link');
        }
    };

    const openFamilyProfileLink = async () => {
        try {
            const res = await studentsApi.getProfileUpdateLink(studentId);
            if (res.success && res.data?.url) {
                const url = res.data.url;
                Alert.alert('Family profile update', url, [
                    { text: 'Open', onPress: () => Linking.openURL(url) },
                    { text: 'Share', onPress: () => Share.share({ message: url }) },
                    { text: 'Cancel', style: 'cancel' },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Could not get profile update link');
        }
    };

    const shiftMonth = (delta: number) => {
        let m = calMonth + delta;
        let y = calYear;
        if (m < 1) {
            m = 12;
            y -= 1;
        }
        if (m > 12) {
            m = 1;
            y += 1;
        }
        setCalMonth(m);
        setCalYear(y);
    };

    const statusColor = (s: string, excused: boolean) => {
        if (s === 'absent' && excused) return BRAND.warning;
        if (s === 'present') return BRAND.success;
        if (s === 'late') return BRAND.warning;
        if (s === 'absent') return BRAND.danger;
        return colors.textSubLight;
    };

    const feeBalance = stats?.fees_balance ?? student?.fees_balance;

    if (loading || !student) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <Text style={[styles.loading, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Loading...
                </Text>
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Profile
                </Text>
                {canManageStudents ? (
                    <TouchableOpacity onPress={() => navigation.navigate('EditStudent', { studentId })}>
                        <Icon name="edit" size={24} color={BRAND.primary} />
                    </TouchableOpacity>
                ) : (
                    <View style={{ width: 24 }} />
                )}
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
                <Card
                    style={[
                        styles.profileCard,
                        { borderRadius: RADIUS.card, backgroundColor: isDark ? colors.surfaceDark : BRAND.surface },
                    ]}
                >
                    <View style={styles.profileHeader}>
                        <Avatar name={student.full_name} imageUrl={student.avatar} size={80} />
                        <View style={styles.profileInfo}>
                            <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {student.full_name}
                            </Text>
                            <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {student.admission_number}
                                {student.class_name ? ` · ${student.class_name}${student.stream_name ? ' ' + student.stream_name : ''}` : ''}
                            </Text>
                            <StatusBadge status={student.status} />
                        </View>
                    </View>
                </Card>

                <View style={styles.tabs}>
                    {tabs.map((tab) => (
                        <TouchableOpacity
                            key={tab.key}
                            style={[
                                styles.tab,
                                activeTab === tab.key && { borderBottomColor: BRAND.primary, borderBottomWidth: 2 },
                            ]}
                            onPress={() => setActiveTab(tab.key)}
                        >
                            <Icon
                                name={tab.icon}
                                size={20}
                                color={activeTab === tab.key ? BRAND.primary : isDark ? colors.textSubDark : colors.textSubLight}
                            />
                            <Text
                                style={[
                                    styles.tabLabel,
                                    {
                                        color: activeTab === tab.key ? BRAND.primary : isDark ? colors.textSubDark : colors.textSubLight,
                                    },
                                ]}
                            >
                                {tab.label}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>

                <View style={styles.content}>
                    {activeTab === 'overview' && (
                        <View>
                            <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Personal
                                </Text>
                                <InfoRow label="Admission date" value={student.admission_date ? formatters.formatDate(student.admission_date) : '—'} isDark={isDark} colors={colors} />
                                <InfoRow label="Class" value={`${student.class_name || '—'} ${student.stream_name || ''}`} isDark={isDark} colors={colors} />
                                <InfoRow label="Date of Birth" value={formatters.formatDate(student.date_of_birth)} isDark={isDark} colors={colors} />
                                <InfoRow label="Gender" value={formatters.capitalize(student.gender)} isDark={isDark} colors={colors} />
                                <InfoRow label="Blood Group" value={student.blood_group || '—'} isDark={isDark} colors={colors} />
                            </Card>

                            <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Contact
                                </Text>
                                <InfoRow label="Phone" value={student.phone || '—'} isDark={isDark} colors={colors} />
                                <InfoRow label="Email" value={student.email || '—'} isDark={isDark} colors={colors} />
                                <InfoRow label="Address" value={student.address || '—'} isDark={isDark} colors={colors} />
                            </Card>

                            {student.parent && (
                                <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        Parents / guardians
                                    </Text>
                                    {!!student.parent.father_name && (
                                        <InfoRow label="Father" value={student.parent.father_name} isDark={isDark} colors={colors} />
                                    )}
                                    {!!student.parent.father_phone && (
                                        <InfoRow label="Father phone" value={student.parent.father_phone} isDark={isDark} colors={colors} />
                                    )}
                                    {!!student.parent.mother_name && (
                                        <InfoRow label="Mother" value={student.parent.mother_name} isDark={isDark} colors={colors} />
                                    )}
                                    {!!student.parent.mother_phone && (
                                        <InfoRow label="Mother phone" value={student.parent.mother_phone} isDark={isDark} colors={colors} />
                                    )}
                                    {!!student.parent.guardian_name && (
                                        <InfoRow label="Guardian" value={student.parent.guardian_name} isDark={isDark} colors={colors} />
                                    )}
                                    {!!student.parent.guardian_phone && (
                                        <InfoRow label="Guardian phone" value={student.parent.guardian_phone} isDark={isDark} colors={colors} />
                                    )}
                                </Card>
                            )}

                            {student.guardians && student.guardians.length > 0 && (
                                <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        Linked contacts
                                    </Text>
                                    {student.guardians.map((guardian, index) => (
                                        <View key={guardian.id ?? index} style={styles.guardianInfo}>
                                            <Text style={[styles.guardianName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                                {guardian.full_name || guardian.name} ({formatters.capitalize(guardian.relationship)})
                                            </Text>
                                            <Text style={[styles.guardianContact, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                                {guardian.phone}
                                                {guardian.email ? ` · ${guardian.email}` : ''}
                                            </Text>
                                        </View>
                                    ))}
                                </Card>
                            )}

                            {canManageStudents && (
                                <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        Family portal
                                    </Text>
                                    <Text style={[styles.placeholder, { color: isDark ? colors.textSubDark : colors.textSubLight, marginBottom: SPACING.md }]}>
                                        Share a link so parents can confirm or update profile details on the school website.
                                    </Text>
                                    <Button title="Get profile update link" onPress={openFamilyProfileLink} style={styles.actionButton} />
                                </Card>
                            )}
                        </View>
                    )}

                    {activeTab === 'attendance' && (
                        <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Attendance (last 90 days)
                            </Text>
                            {stats?.attendance_percentage != null ? (
                                <Text style={[styles.bigStat, { color: BRAND.primary }]}>
                                    {stats.attendance_percentage}%
                                    <Text style={{ fontSize: FONT_SIZES.sm, color: isDark ? colors.textSubDark : colors.textSubLight }}>
                                        {' '}
                                        ({stats.attendance_days_marked ?? 0} days marked)
                                    </Text>
                                </Text>
                            ) : (
                                <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight }}>No recent attendance yet.</Text>
                            )}

                            <View style={styles.calNav}>
                                <TouchableOpacity onPress={() => shiftMonth(-1)} style={styles.calNavBtn}>
                                    <Icon name="chevron-left" size={24} color={BRAND.primary} />
                                </TouchableOpacity>
                                <Text style={[styles.calTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {calMonth}/{calYear}
                                </Text>
                                <TouchableOpacity onPress={() => shiftMonth(1)} style={styles.calNavBtn}>
                                    <Icon name="chevron-right" size={24} color={BRAND.primary} />
                                </TouchableOpacity>
                            </View>

                            {loadingCal ? (
                                <ActivityIndicator color={BRAND.primary} style={{ marginVertical: SPACING.lg }} />
                            ) : (
                                <View style={styles.calGrid}>
                                    {calRows.map((row) => {
                                        const day = row.date.slice(8, 10);
                                        return (
                                            <View
                                                key={row.date}
                                                style={[
                                                    styles.calCell,
                                                    {
                                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                                    },
                                                ]}
                                            >
                                                <Text style={[styles.calDay, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                                    {day}
                                                </Text>
                                                <View style={[styles.calDot, { backgroundColor: statusColor(row.status, row.is_excused) }]} />
                                            </View>
                                        );
                                    })}
                                </View>
                            )}
                            <Text style={[styles.legend, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Green present · Amber late · Red absent · Excused absent uses amber ring in portal; here shown as amber
                            </Text>
                        </Card>
                    )}

                    {activeTab === 'academics' && (
                        <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Academics
                            </Text>
                            <Text style={[styles.placeholder, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Exam marks and report cards can be linked here in a future update. Use the Exams and Marks entry from the teacher dashboard for now.
                            </Text>
                        </Card>
                    )}

                    {activeTab === 'finance' && (
                        <Card style={[styles.infoCard, { borderRadius: RADIUS.card }]}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Finance
                            </Text>
                            {feeBalance !== undefined && feeBalance !== null && (
                                <View style={styles.financeRow}>
                                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Outstanding</Text>
                                    <Text style={[styles.balanceAmount, { color: feeBalance > 0 ? colors.error : colors.success }]}>
                                        {formatters.formatCurrency(feeBalance)}
                                    </Text>
                                </View>
                            )}
                            <Button
                                title="Fee statement"
                                onPress={() =>
                                    navigation.navigate('StudentStatement', {
                                        studentId: student.id,
                                        studentName: student.full_name,
                                    })
                                }
                                style={styles.actionButton}
                            />
                            {!isParentUser && (
                                <Button
                                    title="Record payment"
                                    onPress={() => navigation.navigate('RecordPayment', { studentId: student.id })}
                                    variant="outline"
                                    style={styles.actionButton}
                                />
                            )}
                            {canFinanceMpesa && (
                                <>
                                    <Button
                                        title="Prompt parent (M-Pesa)"
                                        onPress={() => setMpesaOpen(true)}
                                        style={styles.actionButton}
                                    />
                                    <Button
                                        title="Parent payment link"
                                        onPress={openPaymentLinkForParent}
                                        variant="outline"
                                        style={styles.actionButton}
                                    />
                                </>
                            )}
                            {isParentUser && !canFinanceMpesa && (
                                <Button
                                    title="Open payment page"
                                    onPress={openPaymentLinkForParent}
                                    style={styles.actionButton}
                                />
                            )}
                        </Card>
                    )}
                </View>
            </ScrollView>
            <MpesaPromptModal
                visible={mpesaOpen}
                studentId={studentId}
                defaultAmount={
                    feeBalance != null && feeBalance > 0 ? String(Math.ceil(feeBalance)) : ''
                }
                onClose={() => setMpesaOpen(false)}
                navigation={navigation}
            />
        </SafeAreaView>
    );
};

const InfoRow: React.FC<{ label: string; value: string; isDark: boolean; colors: any }> = ({
    label,
    value,
    isDark,
    colors,
}) => (
    <View style={styles.infoRow}>
        <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>{label}:</Text>
        <Text style={[styles.value, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{value}</Text>
    </View>
);

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    loading: { textAlign: 'center', marginTop: SPACING.xxl },
    profileCard: { marginHorizontal: SPACING.xl, marginTop: SPACING.md },
    profileHeader: { flexDirection: 'row', gap: SPACING.md },
    profileInfo: { flex: 1, gap: 4 },
    studentName: { fontSize: FONT_SIZES.lg, fontWeight: 'bold' },
    admissionNumber: { fontSize: FONT_SIZES.sm },
    tabs: { flexDirection: 'row', paddingHorizontal: SPACING.md, marginTop: SPACING.lg, flexWrap: 'wrap' },
    tab: {
        flex: 1,
        minWidth: '22%',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 4,
        paddingVertical: SPACING.sm,
    },
    tabLabel: { fontSize: FONT_SIZES.xs, fontWeight: '600' },
    content: { padding: SPACING.xl },
    infoCard: { marginBottom: SPACING.md },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: 'bold', marginBottom: SPACING.sm },
    infoRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: SPACING.xs },
    label: { fontSize: FONT_SIZES.sm, flex: 1 },
    value: { fontSize: FONT_SIZES.sm, fontWeight: '600', flex: 1, textAlign: 'right' },
    guardianInfo: { paddingVertical: SPACING.xs },
    guardianName: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    guardianContact: { fontSize: FONT_SIZES.xs },
    placeholder: { textAlign: 'left', paddingVertical: SPACING.lg, lineHeight: 20 },
    financeRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: SPACING.md },
    balanceAmount: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    actionButton: { marginTop: SPACING.sm },
    bigStat: { fontSize: FONT_SIZES.xxl, fontWeight: '800', marginBottom: SPACING.md },
    calNav: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: SPACING.lg, marginVertical: SPACING.md },
    calNavBtn: { padding: SPACING.sm },
    calTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    calGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.sm },
    calCell: { width: 48, padding: 6, borderRadius: BORDER_RADIUS.md, borderWidth: 1, alignItems: 'center' },
    calDay: { fontSize: FONT_SIZES.xs },
    calDot: { width: 10, height: 10, borderRadius: 5, marginTop: 4 },
    legend: { fontSize: FONT_SIZES.xs, marginTop: SPACING.md, lineHeight: 16 },
});
