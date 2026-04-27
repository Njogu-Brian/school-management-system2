import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { Button } from '@components/common/Button';
import { LoadingState } from '@components/common/EmptyState';
import { authApi } from '@api/auth.api';
import { hrApi } from '@api/hr.api';
import { User } from 'types/auth.types';
import { Staff } from 'types/hr.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';

interface MyProfileScreenProps {
    navigation: any;
}

function DetailLine({
    label,
    value,
    isDark,
    colors,
}: {
    label: string;
    value?: string | number | null;
    isDark: boolean;
    colors: { textMainDark: string; textMainLight: string; textSubDark: string; textSubLight: string };
}) {
    if (value === undefined || value === null || String(value).trim() === '') return null;
    return (
        <View style={detailStyles.row}>
            <Text style={[detailStyles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>{label}</Text>
            <Text style={[detailStyles.value, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{value}</Text>
        </View>
    );
}

const detailStyles = StyleSheet.create({
    row: { marginBottom: SPACING.sm },
    label: { fontSize: FONT_SIZES.xs, marginBottom: 2 },
    value: { fontSize: FONT_SIZES.md },
});

export const MyProfileScreen: React.FC<MyProfileScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user: authUser } = useAuth();

    const [user, setUser] = useState<User | null>(authUser || null);
    const [staff, setStaff] = useState<Staff | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const loadProfile = async () => {
        try {
            setLoading(true);
            const profileRes = await authApi.getProfile();
            if (profileRes.success && profileRes.data) {
                setUser(profileRes.data);
                const u = profileRes.data as User;
                const sid = u.staff_id ?? u.teacher_id;
                if (sid) {
                    try {
                        const staffRes = await hrApi.getStaffMember(sid);
                        if (staffRes.success && staffRes.data) {
                            setStaff(staffRes.data);
                        }
                    } catch {
                        Alert.alert('Profile', 'Could not load full staff record. Try again or contact ICT.');
                    }
                } else {
                    setStaff(null);
                }
            }
        } catch (error: any) {
            if (authUser) {
                setUser(authUser);
            }
            Alert.alert('Error', error.message || 'Failed to load profile');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        loadProfile();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        loadProfile();
    };

    if (loading && !user) {
        return <LoadingState message="Loading profile..." />;
    }

    const displayName = staff?.full_name || user?.name || 'Staff';
    const workEmail = staff?.work_email || user?.email || '';
    const phone = staff?.phone_number || staff?.phone || user?.phone || '';
    const sid = user?.staff_id ?? user?.teacher_id;

    return (
        <SafeAreaView
            style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                }
            >
                <Card style={styles.avatarCard}>
                    <View style={styles.avatarRow}>
                        <Avatar
                            name={displayName}
                            size={80}
                            imageUrl={staff?.avatar || user?.avatar}
                        />
                        <View style={styles.avatarInfo}>
                            <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{displayName}</Text>
                            {staff?.designation || staff?.job_title ? (
                                <Text style={[styles.role, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {staff?.designation || staff?.job_title}
                                </Text>
                            ) : null}
                            {staff?.employee_number ? (
                                <Text style={[styles.empNo, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    #{staff.employee_number}
                                </Text>
                            ) : null}
                        </View>
                    </View>
                    {sid ? (
                        <Button
                            title="Edit profile"
                            onPress={() => navigation.navigate('StaffEdit', { staffId: sid })}
                            style={{ marginTop: SPACING.md }}
                        />
                    ) : null}
                </Card>

                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Contact</Text>
                    <Card>
                        <DetailLine label="Work email" value={workEmail} isDark={isDark} colors={colors} />
                        <DetailLine label="Personal email" value={staff?.personal_email} isDark={isDark} colors={colors} />
                        <DetailLine label="Phone" value={phone ? formatters.formatPhoneNumber(phone) : ''} isDark={isDark} colors={colors} />
                    </Card>
                </View>

                {staff && (
                    <>
                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Identity & personal
                            </Text>
                            <Card>
                                <DetailLine label="ID / NID" value={staff.id_number} isDark={isDark} colors={colors} />
                                <DetailLine label="Gender" value={staff.gender} isDark={isDark} colors={colors} />
                                <DetailLine label="Date of birth" value={staff.date_of_birth} isDark={isDark} colors={colors} />
                                <DetailLine label="Marital status" value={staff.marital_status} isDark={isDark} colors={colors} />
                            </Card>
                        </View>

                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Employment</Text>
                            <Card>
                                <DetailLine label="Department" value={staff.department} isDark={isDark} colors={colors} />
                                <DetailLine label="Staff category" value={staff.staff_category} isDark={isDark} colors={colors} />
                                <DetailLine label="Employment type" value={staff.employment_type} isDark={isDark} colors={colors} />
                                <DetailLine label="Status" value={staff.status} isDark={isDark} colors={colors} />
                                <DetailLine label="Hire date" value={staff.hire_date} isDark={isDark} colors={colors} />
                                <DetailLine label="Max lessons / week" value={staff.max_lessons_per_week} isDark={isDark} colors={colors} />
                                <DetailLine label="Supervisor" value={staff.supervisor_name} isDark={isDark} colors={colors} />
                            </Card>
                        </View>

                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Address</Text>
                            <Card>
                                <DetailLine label="Residential" value={staff.residential_address} isDark={isDark} colors={colors} />
                            </Card>
                        </View>

                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Emergency contact
                            </Text>
                            <Card>
                                <DetailLine label="Name" value={staff.emergency_contact_name} isDark={isDark} colors={colors} />
                                <DetailLine
                                    label="Relationship"
                                    value={staff.emergency_contact_relationship}
                                    isDark={isDark}
                                    colors={colors}
                                />
                                <DetailLine label="Phone" value={staff.emergency_contact_phone} isDark={isDark} colors={colors} />
                            </Card>
                        </View>

                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Tax & statutory</Text>
                            <Card>
                                <DetailLine label="KRA PIN" value={staff.kra_pin} isDark={isDark} colors={colors} />
                                <DetailLine label="NSSF" value={staff.nssf} isDark={isDark} colors={colors} />
                                <DetailLine label="NHIF" value={staff.nhif} isDark={isDark} colors={colors} />
                            </Card>
                        </View>

                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Banking</Text>
                            <Card>
                                <DetailLine label="Bank" value={staff.bank_name} isDark={isDark} colors={colors} />
                                <DetailLine label="Branch" value={staff.bank_branch} isDark={isDark} colors={colors} />
                                <DetailLine label="Account" value={staff.bank_account} isDark={isDark} colors={colors} />
                            </Card>
                        </View>

                    </>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    avatarCard: { marginBottom: SPACING.lg },
    avatarRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.lg },
    avatarInfo: { flex: 1 },
    name: { fontSize: FONT_SIZES.xl, fontWeight: 'bold', marginBottom: 4 },
    role: { fontSize: FONT_SIZES.md, marginBottom: 2 },
    empNo: { fontSize: FONT_SIZES.sm },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: SPACING.sm },
});
