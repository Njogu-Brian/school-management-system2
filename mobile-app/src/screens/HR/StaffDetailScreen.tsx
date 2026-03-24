import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Avatar } from '@components/common/Avatar';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import { Staff } from '../types/hr.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StaffDetailScreenProps {
    navigation: any;
    route: any;
}

export const StaffDetailScreen: React.FC<StaffDetailScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
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
                    {staff.work_email && (
                        <View style={styles.row}>
                            <Icon name="email" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.work_email}
                            </Text>
                        </View>
                    )}
                    {staff.department && (
                        <View style={styles.row}>
                            <Icon name="business" size={18} color={colors.primary} />
                            <Text style={[styles.rowText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {staff.department}
                            </Text>
                        </View>
                    )}
                </Card>
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
    notFound: { textAlign: 'center', padding: SPACING.xl },
});
