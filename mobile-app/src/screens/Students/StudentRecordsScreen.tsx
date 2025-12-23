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
import { Button } from '@components/common/Button';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StudentRecordsScreenProps {
    navigation: any;
    route: any;
}

export const StudentRecordsScreen: React.FC<StudentRecordsScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { studentId, studentName } = route.params || {};

    const [activeTab, setActiveTab] = useState<'medical' | 'disciplinary' | 'academic'>('medical');
    const [refreshing, setRefreshing] = useState(false);

    const [records] = useState({
        medical: [
            {
                id: 1,
                date: '2024-01-15',
                type: 'checkup',
                description: 'Annual health checkup',
                doctor: 'Dr. Smith',
                notes: 'All vitals normal',
            },
            {
                id: 2,
                date: '2024-02-20',
                type: 'illness',
                description: 'Flu symptoms',
                doctor: 'Dr. Johnson',
                notes: 'Prescribed medication, 3 days rest',
            },
        ],
        disciplinary: [
            {
                id: 1,
                date: '2024-03-10',
                type: 'warning',
                description: 'Late attendance',
                action: 'Verbal warning',
                resolved: true,
            },
        ],
        academic: [
            {
                id: 1,
                term: 'Term 1',
                year: '2024',
                position: 5,
                totalStudents: 50,
                average: 78.5,
                grade: 'B+',
            },
        ],
    });

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 1000);
    };

    const renderMedicalRecord = (record: any) => (
        <Card key={record.id} style={styles.recordCard}>
            <View style={styles.recordHeader}>
                <View style={[styles.typeIndicator, { backgroundColor: colors.success + '20' }]}>
                    <Icon name="medical-services" size={20} color={colors.success} />
                </View>
                <View style={styles.recordInfo}>
                    <Text style={[styles.recordTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {record.description}
                    </Text>
                    <Text style={[styles.recordDate, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {formatters.formatDate(record.date)} • Dr. {record.doctor}
                    </Text>
                    <Text style={[styles.recordNotes, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {record.notes}
                    </Text>
                </View>
            </View>
        </Card>
    );

    const renderDisciplinaryRecord = (record: any) => (
        <Card key={record.id} style={styles.recordCard}>
            <View style={styles.recordHeader}>
                <View style={[styles.typeIndicator, { backgroundColor: colors.error + '20' }]}>
                    <Icon name="warning" size={20} color={colors.error} />
                </View>
                <View style={styles.recordInfo}>
                    <View style={styles.titleRow}>
                        <Text style={[styles.recordTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {record.description}
                        </Text>
                        {record.resolved && (
                            <View style={[styles.resolvedBadge, { backgroundColor: colors.success + '20' }]}>
                                <Text style={[styles.resolvedText, { color: colors.success }]}>Resolved</Text>
                            </View>
                        )}
                    </View>
                    <Text style={[styles.recordDate, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {formatters.formatDate(record.date)}
                    </Text>
                    <Text style={[styles.recordAction, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Action: {record.action}
                    </Text>
                </View>
            </View>
        </Card>
    );

    const renderAcademicRecord = (record: any) => (
        <Card key={record.id} style={styles.recordCard}>
            <View style={styles.academicRecord}>
                <Text style={[styles.termTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {record.term} {record.year}
                </Text>

                <View style={styles.academicStats}>
                    <View style={styles.statBox}>
                        <Text style={[styles.statValue, { color: colors.primary }]}>{record.position}</Text>
                        <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Position
                        </Text>
                    </View>

                    <View style={styles.statBox}>
                        <Text style={[styles.statValue, { color: formatters.getGradeColor(record.grade) }]}>
                            {record.grade}
                        </Text>
                        <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Grade
                        </Text>
                    </View>

                    <View style={styles.statBox}>
                        <Text style={[styles.statValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {record.average}%
                        </Text>
                        <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Average
                        </Text>
                    </View>
                </View>
            </View>
        </Card>
    );

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Student Records
                </Text>
                {studentName && (
                    <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {studentName}
                    </Text>
                )}
            </View>

            {/* Tabs */}
            <View style={styles.tabs}>
                {(['medical', 'disciplinary', 'academic'] as const).map((tab) => (
                    <TouchableOpacity
                        key={tab}
                        style={[
                            styles.tab,
                            activeTab === tab && { borderBottomColor: colors.primary, borderBottomWidth: 2 },
                        ]}
                        onPress={() => setActiveTab(tab)}
                    >
                        <Text
                            style={[
                                styles.tabText,
                                {
                                    color: activeTab === tab ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight,
                                    fontWeight: activeTab === tab ? 'bold' : 'normal',
                                },
                            ]}
                        >
                            {formatters.capitalize(tab)}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>

            <ScrollView
                style={styles.content}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
            >
                {activeTab === 'medical' && (
                    <>
                        <Button
                            title="Add Medical Record"
                            onPress={() => navigation.navigate('AddMedicalRecord', { studentId })}
                            icon="add"
                            variant="outline"
                            fullWidth
                            style={styles.addButton}
                        />
                        {records.medical.map(renderMedicalRecord)}
                    </>
                )}

                {activeTab === 'disciplinary' && (
                    <>
                        <Button
                            title="Add Disciplinary Record"
                            onPress={() => navigation.navigate('AddDisciplinaryRecord', { studentId })}
                            icon="add"
                            variant="outline"
                            fullWidth
                            style={styles.addButton}
                        />
                        {records.disciplinary.map(renderDisciplinaryRecord)}
                    </>
                )}

                {activeTab === 'academic' && (
                    <>
                        {records.academic.map(renderAcademicRecord)}
                    </>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { paddingHorizontal: SPACING.xl, paddingVertical: SPACING.md },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    subtitle: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    tabs: { flexDirection: 'row', paddingHorizontal: SPACING.xl, marginBottom: SPACING.md },
    tab: { flex: 1, paddingVertical: SPACING.sm, alignItems: 'center' },
    tabText: { fontSize: FONT_SIZES.sm },
    content: { flex: 1, padding: SPACING.xl },
    addButton: { marginBottom: SPACING.md },
    recordCard: { marginBottom: SPACING.md },
    recordHeader: { flexDirection: 'row', gap: SPACING.md },
    typeIndicator: { width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
    recordInfo: { flex: 1, gap: 4 },
    titleRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    recordTitle: { fontSize: FONT_SIZES.md, fontWeight: '600', flex: 1 },
    recordDate: { fontSize: FONT_SIZES.xs },
    recordNotes: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    recordAction: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    resolvedBadge: { paddingHorizontal: SPACING.xs, paddingVertical: 2, borderRadius: 4 },
    resolvedText: { fontSize: 10, fontWeight: 'bold' },
    academicRecord: { gap: SPACING.md },
    termTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold' },
    academicStats: { flexDirection: 'row', gap: SPACING.md },
    statBox: { flex: 1, alignItems: 'center', padding: SPACING.md, backgroundColor: '#f8fafc', borderRadius: 8 },
    statValue: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    statLabel: { fontSize: FONT_SIZES.xs, marginTop: 4 },
});
