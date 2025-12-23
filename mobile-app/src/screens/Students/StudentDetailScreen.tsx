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
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { StatusBadge } from '@components/common/StatusBadge';
import { studentsApi } from '@api/students.api';
import { financeApi } from '@api/finance.api';
import { Student } from '../types/student.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StudentDetailScreenProps {
    navigation: any;
    route: any;
}

export const StudentDetailScreen: React.FC<StudentDetailScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { studentId } = route.params;

    const [student, setStudent] = useState<Student | null>(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        loadStudent();
    }, [studentId]);

    const loadStudent = async () => {
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
    };

    const tabs = [
        { key: 'overview', label: 'Overview', icon: 'info' },
        { key: 'attendance', label: 'Attendance', icon: 'event' },
        { key: 'academics', label: 'Academics', icon: 'school' },
        { key: 'finance', label: 'Finance', icon: 'payments' },
    ];

    if (loading || !student) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <Text style={[styles.loading, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Loading...
                </Text>
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Student Details
                </Text>
                <TouchableOpacity onPress={() => navigation.navigate('EditStudent', { studentId })}>
                    <Icon name="edit" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            <ScrollView>
                {/* Profile Card */}
                <Card style={styles.profileCard}>
                    <View style={styles.profileHeader}>
                        <Avatar name={student.full_name} imageUrl={student.avatar} size={80} />
                        <View style={styles.profileInfo}>
                            <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {student.full_name}
                            </Text>
                            <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {student.admission_number}
                            </Text>
                            <StatusBadge status={student.status} />
                        </View>
                    </View>
                </Card>

                {/* Tabs */}
                <View style={styles.tabs}>
                    {tabs.map((tab) => (
                        <TouchableOpacity
                            key={tab.key}
                            style={[
                                styles.tab,
                                activeTab === tab.key && { borderBottomColor: colors.primary, borderBottomWidth: 2 },
                            ]}
                            onPress={() => setActiveTab(tab.key)}
                        >
                            <Icon
                                name={tab.icon}
                                size={20}
                                color={activeTab === tab.key ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight}
                            />
                            <Text
                                style={[
                                    styles.tabLabel,
                                    {
                                        color: activeTab === tab.key ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight,
                                    },
                                ]}
                            >
                                {tab.label}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>

                {/* Tab Content */}
                <View style={styles.content}>
                    {activeTab === 'overview' && (
                        <View>
                            <Card style={styles.infoCard}>
                                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Personal Information
                                </Text>
                                <InfoRow label="Class" value={`${student.class_name} ${student.stream_name || ''}`} isDark={isDark} colors={colors} />
                                <InfoRow label="Date of Birth" value={formatters.formatDate(student.date_of_birth)} isDark={isDark} colors={colors} />
                                <InfoRow label="Gender" value={formatters.capitalize(student.gender)} isDark={isDark} colors={colors} />
                                <InfoRow label="Blood Group" value={student.blood_group || 'N/A'} isDark={isDark} colors={colors} />
                            </Card>

                            <Card style={styles.infoCard}>
                                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    Contact Information
                                </Text>
                                <InfoRow label="Email" value={student.email || 'N/A'} isDark={isDark} colors={colors} />
                                <InfoRow label="Phone" value={student.phone || 'N/A'} isDark={isDark} colors={colors} />
                                <InfoRow label="Address" value={student.address || 'N/A'} isDark={isDark} colors={colors} />
                            </Card>

                            {student.guardians && student.guardians.length > 0 && (
                                <Card style={styles.infoCard}>
                                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        Guardian Information
                                    </Text>
                                    {student.guardians.map((guardian, index) => (
                                        <View key={index} style={styles.guardianInfo}>
                                            <Text style={[styles.guardianName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                                {guardian.full_name} ({formatters.capitalize(guardian.relationship)})
                                            </Text>
                                            <Text style={[styles.guardianContact, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                                {guardian.phone}
                                            </Text>
                                        </View>
                                    ))}
                                </Card>
                            )}
                        </View>
                    )}

                    {activeTab === 'attendance' && (
                        <Card style={styles.infoCard}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Attendance Summary
                            </Text>
                            <Text style={[styles.placeholder, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Attendance data will be displayed here
                            </Text>
                        </Card>
                    )}

                    {activeTab === 'academics' && (
                        <Card style={styles.infoCard}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Academic Performance
                            </Text>
                            <Text style={[styles.placeholder, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Academic data will be displayed here
                            </Text>
                        </Card>
                    )}

                    {activeTab === 'finance' && (
                        <Card style={styles.infoCard}>
                            <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Fee Status
                            </Text>
                            {student.fees_balance !== undefined && (
                                <View style={styles.financeRow}>
                                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        Balance:
                                    </Text>
                                    <Text style={[styles.balanceAmount, { color: student.fees_balance > 0 ? colors.error : colors.success }]}>
                                        {formatters.formatCurrency(student.fees_balance)}
                                    </Text>
                                </View>
                            )}
                            <Button
                                title="View Statement"
                                onPress={() => navigation.navigate('StudentStatement', { studentId: student.id })}
                                style={styles.actionButton}
                            />
                            <Button
                                title="Record Payment"
                                onPress={() => navigation.navigate('RecordPayment', { studentId: student.id })}
                                variant="outline"
                                style={styles.actionButton}
                            />
                        </Card>
                    )}
                </View>
            </ScrollView>
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
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: {
        fontSize: FONT_SIZES.xl,
        fontWeight: 'bold',
    },
    loading: {
        textAlign: 'center',
        marginTop: SPACING.xxl,
    },
    profileCard: {
        marginHorizontal: SPACING.xl,
        marginTop: SPACING.md,
    },
    profileHeader: {
        flexDirection: 'row',
        gap: SPACING.md,
    },
    profileInfo: {
        flex: 1,
        gap: 4,
    },
    studentName: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
    },
    admissionNumber: {
        fontSize: FONT_SIZES.sm,
    },
    tabs: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.xl,
        marginTop: SPACING.lg,
        gap: SPACING.sm,
    },
    tab: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 4,
        paddingVertical: SPACING.sm,
    },
    tabLabel: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
    content: {
        padding: SPACING.xl,
    },
    infoCard: {
        marginBottom: SPACING.md,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
        marginBottom: SPACING.sm,
    },
    infoRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.xs,
    },
    label: {
        fontSize: FONT_SIZES.sm,
    },
    value: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    guardianInfo: {
        paddingVertical: SPACING.xs,
    },
    guardianName: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    guardianContact: {
        fontSize: FONT_SIZES.xs,
    },
    placeholder: {
        textAlign: 'center',
        paddingVertical: SPACING.lg,
    },
    financeRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.md,
    },
    balanceAmount: {
        fontSize: FONT_SIZES.xl,
        fontWeight: 'bold',
    },
    actionButton: {
        marginTop: SPACING.sm,
    },
});
