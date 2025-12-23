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
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { attendanceApi } from '@api/attendance.api';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface AttendanceRecordsScreenProps {
    navigation: any;
    route: any;
}

export const AttendanceRecordsScreen: React.FC<AttendanceRecordsScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { studentId, classId } = route.params || {};

    const [records, setRecords] = useState<any[]>([]);
    const [summary, setSummary] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth());
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());

    useEffect(() => {
        loadRecords();
    }, [selectedMonth, selectedYear]);

    const loadRecords = async () => {
        try {
            setLoading(true);

            const startDate = new Date(selectedYear, selectedMonth, 1).toISOString().split('T')[0];
            const endDate = new Date(selectedYear, selectedMonth + 1, 0).toISOString().split('T')[0];

            const filters: any = {
                date_from: startDate,
                date_to: endDate,
                per_page: 100,
            };

            if (studentId) {
                filters.student_id = studentId;
            } else if (classId) {
                filters.class_id = classId;
            }

            const response = await attendanceApi.getAttendance(filters);

            if (response.success && response.data) {
                setRecords(response.data.data);

                // Calculate summary
                const present = response.data.data.filter((r: any) => r.status === 'present').length;
                const absent = response.data.data.filter((r: any) => r.status === 'absent').length;
                const late = response.data.data.filter((r: any) => r.status === 'late').length;
                const total = response.data.data.length;

                setSummary({
                    present,
                    absent,
                    late,
                    excused: response.data.data.filter((r: any) => r.status === 'excused').length,
                    total,
                    percentage: total > 0 ? ((present + late) / total * 100).toFixed(1) : 0,
                });
            }
        } catch (error: any) {
            Alert.alert('Error', 'Failed to load attendance records');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        loadRecords();
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'present': return colors.success;
            case 'absent': return colors.error;
            case 'late': return '#f59e0b';
            case 'excused': return '#3b82f6';
            default: return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'present': return 'check-circle';
            case 'absent': return 'cancel';
            case 'late': return 'schedule';
            case 'excused': return 'info';
            default: return 'help';
        }
    };

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <LoadingState message="Loading attendance records..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Attendance Records
                </Text>
            </View>

            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} tintColor={colors.primary} />}
            >
                {/* Summary Card */}
                {summary && (
                    <Card style={styles.summaryCard}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Summary ({new Date(selectedYear, selectedMonth).toLocaleString('default', { month: 'long', year: 'numeric' })})
                        </Text>

                        <View style={styles.summaryGrid}>
                            <View style={styles.summaryItem}>
                                <Text style={[styles.summaryValue, { color: colors.success }]}>{summary.present}</Text>
                                <Text style={[styles.summaryLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Present</Text>
                            </View>

                            <View style={styles.summaryItem}>
                                <Text style={[styles.summaryValue, { color: colors.error }]}>{summary.absent}</Text>
                                <Text style={[styles.summaryLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Absent</Text>
                            </View>

                            <View style={styles.summaryItem}>
                                <Text style={[styles.summaryValue, { color: '#f59e0b' }]}>{summary.late}</Text>
                                <Text style={[styles.summaryLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Late</Text>
                            </View>

                            <View style={styles.summaryItem}>
                                <Text style={[styles.summaryValue, { color: colors.primary }]}>{summary.percentage}%</Text>
                                <Text style={[styles.summaryLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Rate</Text>
                            </View>
                        </View>
                    </Card>
                )}

                {/* Records List */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Daily Records
                    </Text>

                    {records.map((record) => (
                        <Card key={record.id} style={styles.recordCard}>
                            <View style={styles.recordRow}>
                                <View style={[styles.statusIndicator, { backgroundColor: getStatusColor(record.status) }]}>
                                    <Icon name={getStatusIcon(record.status)} size={20} color="#fff" />
                                </View>

                                <View style={styles.recordInfo}>
                                    <Text style={[styles.recordDate, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {formatters.formatDate(record.date)}
                                    </Text>
                                    {record.student_name && (
                                        <Text style={[styles.studentName, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {record.student_name}
                                        </Text>
                                    )}
                                    {record.remarks && (
                                        <Text style={[styles.remarks, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {record.remarks}
                                        </Text>
                                    )}
                                </View>

                                <Text style={[styles.statusText, { color: getStatusColor(record.status) }]}>
                                    {formatters.capitalize(record.status)}
                                </Text>
                            </View>
                        </Card>
                    ))}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { paddingHorizontal: SPACING.xl, paddingVertical: SPACING.md },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    content: { padding: SPACING.xl },
    summaryCard: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold', marginBottom: SPACING.md },
    summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.md },
    summaryItem: { flex: 1, minWidth: '45%', alignItems: 'center', padding: SPACING.md, backgroundColor: '#f8fafc', borderRadius: 8 },
    summaryValue: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    summaryLabel: { fontSize: FONT_SIZES.xs, marginTop: 4 },
    section: { marginTop: SPACING.md },
    recordCard: { marginBottom: SPACING.sm },
    recordRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    statusIndicator: { width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
    recordInfo: { flex: 1 },
    recordDate: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    studentName: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    remarks: { fontSize: FONT_SIZES.xs, marginTop: 4, fontStyle: 'italic' },
    statusText: { fontSize: FONT_SIZES.sm, fontWeight: 'bold' },
});
