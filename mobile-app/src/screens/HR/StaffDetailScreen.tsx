import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Alert,
    Linking,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Avatar } from '@components/common/Avatar';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import { Staff } from '@types/hr.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import { WEB_BASE_URL } from '@utils/env';
import { formatters } from '@utils/formatters';
import { canManageStaff, canViewPayrollRecords } from '@utils/staffHrAccess';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StaffDetailScreenProps {
    navigation: any;
    route: any;
}

export const StaffDetailScreen: React.FC<StaffDetailScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const { staffId } = route.params;
    const [staff, setStaff] = useState<Staff | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const load = async () => {
            try {
                const res = await hrApi.getStaffMember(staffId);
                if (res.success && res.data) setStaff(res.data);
            } catch {
                Alert.alert('Error', 'Failed to load staff details');
            } finally {
                setLoading(false);
            }
        };
        load();
    }, [staffId]);

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <LoadingState message="Loading..." />
            </SafeAreaView>
        );
    }

    if (!staff) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()}>
                        <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Staff Not Found
                    </Text>
                    <View style={{ width: 24 }} />
                </View>
                <Text style={[styles.notFound, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    Staff member could not be loaded.
                </Text>
            </SafeAreaView>
        );
    }

    const workEmail = staff.work_email ?? staff.email;
    const showManage = canManageStaff(user);
    const showPayroll = canViewPayrollRecords(user);
    const portalBase = WEB_BASE_URL?.replace(/\/$/, '') ?? '';

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Staff Details
                </Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView contentContainerStyle={styles.content}>
                <Card style={[styles.profileCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                    <View style={styles.profileHeader}>
                        <Avatar name={staff.full_name} imageUrl={staff.avatar} size={80} />
                        <View style={styles.profileInfo}>
                            <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.full_name}
                            </Text>
                            <Text style={[styles.employeeNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {staff.employee_number}
                            </Text>
                            {(staff.designation || staff.role) && (
                                <Text style={[styles.role, { color: colors.primary }]}>
                                    {staff.designation || staff.role}
                                </Text>
                            )}
                        </View>
                    </View>
                </Card>

                {(showManage || showPayroll || !!portalBase) && (
                    <View style={styles.actionRow}>
                        {showManage ? (
                            <TouchableOpacity
                                style={[styles.actionBtn, { backgroundColor: colors.primary }]}
                                onPress={() => navigation.navigate('StaffEdit', { staffId })}
                            >
                                <Icon name="edit" size={20} color={Palette.onPrimary} />
                                <Text style={styles.actionBtnText}>Edit</Text>
                            </TouchableOpacity>
                        ) : null}
                        {showManage ? (
                            <TouchableOpacity
                                style={[
                                    styles.actionBtn,
                                    {
                                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                        borderWidth: 1,
                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                    },
                                ]}
                                onPress={() => navigation.navigate('ApplyLeave', { staffId })}
                            >
                                <Icon name="event-available" size={20} color={colors.primary} />
                                <Text style={[styles.actionBtnTextOutline, { color: colors.primary }]}>Apply leave</Text>
                            </TouchableOpacity>
                        ) : null}
                        {showPayroll ? (
                            <TouchableOpacity
                                style={[
                                    styles.actionBtn,
                                    {
                                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                        borderWidth: 1,
                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                    },
                                ]}
                                onPress={() =>
                                    navigation.navigate('PayrollRecords', {
                                        staffId,
                                        title: staff.full_name,
                                    })
                                }
                            >
                                <Icon name="payments" size={20} color={colors.primary} />
                                <Text style={[styles.actionBtnTextOutline, { color: colors.primary }]}>Payroll</Text>
                            </TouchableOpacity>
                        ) : null}
                        {portalBase ? (
                            <TouchableOpacity
                                style={[
                                    styles.actionBtn,
                                    {
                                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                        borderWidth: 1,
                                        borderColor: isDark ? colors.borderDark : BRAND.border,
                                    },
                                ]}
                                onPress={() => Linking.openURL(`${portalBase}/staff/${staffId}/edit`)}
                            >
                                <Icon name="open-in-browser" size={20} color={colors.primary} />
                                <Text style={[styles.actionBtnTextOutline, { color: colors.primary }]}>Web portal</Text>
                            </TouchableOpacity>
                        ) : null}
                    </View>
                )}

                <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Contact
                    </Text>
                    {staff.phone && (
                        <View style={styles.row}>
                            <Icon name="phone" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.phone}
                            </Text>
                        </View>
                    )}
                    {workEmail ? (
                        <View style={styles.row}>
                            <Icon name="email" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {workEmail}
                            </Text>
                        </View>
                    ) : null}
                    {staff.personal_email ? (
                        <View style={styles.row}>
                            <Icon name="alternate-email" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.personal_email}
                            </Text>
                        </View>
                    ) : null}
                    {staff.department && (
                        <View style={styles.row}>
                            <Icon name="business" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.department}
                            </Text>
                        </View>
                    )}
                    {staff.id_number ? (
                        <View style={styles.row}>
                            <Icon name="badge" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                ID: {staff.id_number}
                            </Text>
                        </View>
                    ) : null}
                </Card>

                {(staff.date_of_birth || staff.gender || staff.marital_status) && (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Personal
                        </Text>
                        {staff.date_of_birth ? (
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Date of birth: {formatters.formatDate(staff.date_of_birth)}
                            </Text>
                        ) : null}
                        {staff.gender ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    {
                                        marginTop: SPACING.sm,
                                        color: isDark ? colors.textMainDark : colors.textMainLight,
                                    },
                                ]}
                            >
                                Gender: {formatters.capitalize(staff.gender)}
                            </Text>
                        ) : null}
                        {staff.marital_status ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    {
                                        marginTop: SPACING.sm,
                                        color: isDark ? colors.textMainDark : colors.textMainLight,
                                    },
                                ]}
                            >
                                Marital status: {formatters.capitalize(String(staff.marital_status).replace(/_/g, ' '))}
                            </Text>
                        ) : null}
                    </Card>
                )}

                {(staff.hire_date ||
                    staff.termination_date ||
                    staff.employment_status ||
                    staff.employment_type ||
                    staff.contract_start_date ||
                    staff.contract_end_date ||
                    staff.max_lessons_per_week != null ||
                    staff.staff_category ||
                    staff.supervisor_name) && (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Employment
                        </Text>
                        {staff.staff_category ? (
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Category: {staff.staff_category}
                            </Text>
                        ) : null}
                        {staff.hire_date ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Hire date: {formatters.formatDate(staff.hire_date)}
                            </Text>
                        ) : null}
                        {staff.termination_date ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Termination: {formatters.formatDate(staff.termination_date)}
                            </Text>
                        ) : null}
                        {staff.employment_status ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Status: {formatters.capitalize(String(staff.employment_status).replace(/_/g, ' '))}
                            </Text>
                        ) : null}
                        {staff.employment_type ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Type: {formatters.capitalize(String(staff.employment_type).replace(/_/g, ' '))}
                            </Text>
                        ) : null}
                        {staff.contract_start_date ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Contract: {formatters.formatDate(staff.contract_start_date)}
                                {staff.contract_end_date ? ` – ${formatters.formatDate(staff.contract_end_date)}` : ''}
                            </Text>
                        ) : staff.contract_end_date ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Contract ends: {formatters.formatDate(staff.contract_end_date)}
                            </Text>
                        ) : null}
                        {staff.max_lessons_per_week != null ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Max lessons / week: {staff.max_lessons_per_week}
                            </Text>
                        ) : null}
                        {staff.supervisor_name ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textMainDark : colors.textMainLight },
                                ]}
                            >
                                Supervisor: {staff.supervisor_name}
                            </Text>
                        ) : null}
                    </Card>
                )}

                {(staff.kra_pin || staff.nssf || staff.nhif || (staff.statutory_exemptions && staff.statutory_exemptions.length > 0)) && (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Statutory
                        </Text>
                        {staff.kra_pin ? (
                            <View style={styles.row}>
                                <Icon name="gavel" size={18} color={colors.primary} />
                                <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    KRA PIN: {staff.kra_pin}
                                </Text>
                            </View>
                        ) : null}
                        {staff.nssf ? (
                            <View style={[styles.row, { marginTop: SPACING.xs }]}>
                                <Icon name="shield" size={18} color={colors.primary} />
                                <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    NSSF: {staff.nssf}
                                </Text>
                            </View>
                        ) : null}
                        {staff.nhif ? (
                            <View style={[styles.row, { marginTop: SPACING.xs }]}>
                                <Icon name="local-hospital" size={18} color={colors.primary} />
                                <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    NHIF: {staff.nhif}
                                </Text>
                            </View>
                        ) : null}
                        {staff.statutory_exemptions && staff.statutory_exemptions.length > 0 ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    {
                                        marginTop: SPACING.md,
                                        color: isDark ? colors.textSubDark : colors.textSubLight,
                                    },
                                ]}
                            >
                                Exemptions: {staff.statutory_exemptions.map((c) => formatters.capitalize(c.replace(/_/g, ' '))).join(', ')}
                            </Text>
                        ) : null}
                    </Card>
                )}

                {staff.residential_address ? (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Address
                        </Text>
                        <Text style={[styles.blockText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {staff.residential_address}
                        </Text>
                    </Card>
                ) : null}

                {(staff.emergency_contact_name || staff.emergency_contact_phone) && (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Emergency
                        </Text>
                        {staff.emergency_contact_name ? (
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.emergency_contact_name}
                            </Text>
                        ) : null}
                        {staff.emergency_contact_phone ? (
                            <View style={[styles.row, { marginTop: SPACING.sm }]}>
                                <Icon name="phone" size={18} color={colors.primary} />
                                <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {staff.emergency_contact_phone}
                                </Text>
                            </View>
                        ) : null}
                    </Card>
                )}

                {(staff.bank_name || staff.bank_account || staff.bank_branch) && (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Banking
                        </Text>
                        {staff.bank_name ? (
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.bank_name}
                                {staff.bank_branch ? ` · ${staff.bank_branch}` : ''}
                            </Text>
                        ) : null}
                        {staff.bank_account ? (
                            <Text
                                style={[
                                    styles.rowText,
                                    { marginTop: SPACING.sm, color: isDark ? colors.textSubDark : colors.textSubLight },
                                ]}
                            >
                                Acc: {staff.bank_account}
                            </Text>
                        ) : null}
                    </Card>
                )}

                {staff.basic_salary != null && (
                    <Card style={[styles.infoCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Salary
                        </Text>
                        <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Basic: {formatters.formatCurrency(Number(staff.basic_salary))}
                        </Text>
                    </Card>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

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
    content: { padding: SPACING.xl },
    profileCard: { marginBottom: SPACING.lg, padding: SPACING.lg },
    profileHeader: { flexDirection: 'row', alignItems: 'center', gap: SPACING.lg },
    profileInfo: { flex: 1, gap: 4 },
    name: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    employeeNumber: { fontSize: FONT_SIZES.sm },
    role: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    infoCard: { padding: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: 'bold', marginBottom: SPACING.md },
    row: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm, marginBottom: SPACING.sm },
    rowText: { fontSize: FONT_SIZES.md },
    blockText: { fontSize: FONT_SIZES.md, lineHeight: 22 },
    notFound: { textAlign: 'center', padding: SPACING.xl },
    actionRow: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
        marginBottom: SPACING.md,
    },
    actionBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.md,
        borderRadius: RADIUS.button,
    },
    actionBtnText: { color: Palette.onPrimary, fontWeight: '700', fontSize: FONT_SIZES.sm },
    actionBtnTextOutline: { fontWeight: '700', fontSize: FONT_SIZES.sm },
});
