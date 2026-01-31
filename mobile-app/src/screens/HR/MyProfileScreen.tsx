import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { LoadingState } from '@components/common/EmptyState';
import { authApi } from '@api/auth.api';
import { hrApi } from '@api/hr.api';
import { User } from '@types/auth.types';
import { Staff } from '@types/hr.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface MyProfileScreenProps {
    navigation: any;
}

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
                const u = profileRes.data as User & { staff_id?: number };
                if (u.staff_id) {
                    try {
                        const staffRes = await hrApi.getStaffMember(u.staff_id);
                        if (staffRes.success && staffRes.data) {
                            setStaff(staffRes.data);
                        }
                    } catch {
                        // Staff endpoint may not be available for teacher
                    }
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
    const email = staff?.email || user?.email || '';
    const phone = staff?.phone || user?.phone || '';

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    My Profile
                </Text>
            </View>
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                }
            >
                <Card style={styles.avatarCard}>
                    <View style={styles.avatarRow}>
                        <Avatar name={displayName} size={80} source={staff?.avatar ? { uri: staff.avatar } : undefined} />
                        <View style={styles.avatarInfo}>
                            <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {displayName}
                            </Text>
                            {staff?.designation && (
                                <Text style={[styles.role, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {staff.designation}
                                </Text>
                            )}
                            {staff?.employee_number && (
                                <Text style={[styles.empNo, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    #{staff.employee_number}
                                </Text>
                            )}
                        </View>
                    </View>
                </Card>

                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Contact
                    </Text>
                    <Card>
                        {email ? (
                            <View style={styles.row}>
                                <Icon name="email" size={20} color={colors.primary} />
                                <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{email}</Text>
                            </View>
                        ) : null}
                        {phone ? (
                            <View style={styles.row}>
                                <Icon name="phone" size={20} color={colors.primary} />
                                <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {formatters.formatPhoneNumber(phone)}
                                </Text>
                            </View>
                        ) : null}
                    </Card>
                </View>

                {staff && (
                    <View style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Employment
                        </Text>
                        <Card>
                            {staff.department ? (
                                <View style={styles.row}>
                                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Department</Text>
                                    <Text style={[styles.value, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{staff.department}</Text>
                                </View>
                            ) : null}
                            {staff.employment_type ? (
                                <View style={styles.row}>
                                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Type</Text>
                                    <Text style={[styles.value, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {formatters.capitalizeWords(staff.employment_type.replace('_', ' '))}
                                    </Text>
                                </View>
                            ) : null}
                            {staff.employment_date ? (
                                <View style={styles.row}>
                                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Joined</Text>
                                    <Text style={[styles.value, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {formatters.formatDate(staff.employment_date)}
                                    </Text>
                                </View>
                            ) : null}
                            {staff.status ? (
                                <View style={styles.row}>
                                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Status</Text>
                                    <Text style={[styles.value, { color: colors.success }]}>{formatters.capitalize(staff.status)}</Text>
                                </View>
                            ) : null}
                        </Card>
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    backBtn: { marginRight: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    content: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    avatarCard: { marginBottom: SPACING.lg },
    avatarRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.lg },
    avatarInfo: { flex: 1 },
    name: { fontSize: FONT_SIZES.xl, fontWeight: 'bold', marginBottom: 4 },
    role: { fontSize: FONT_SIZES.md, marginBottom: 2 },
    empNo: { fontSize: FONT_SIZES.sm },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: SPACING.sm },
    row: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm, paddingVertical: SPACING.sm },
    rowText: { fontSize: FONT_SIZES.md, flex: 1 },
    label: { fontSize: FONT_SIZES.sm, width: 100 },
    value: { fontSize: FONT_SIZES.md, flex: 1 },
});
