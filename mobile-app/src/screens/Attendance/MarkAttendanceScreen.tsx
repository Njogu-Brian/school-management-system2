import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    FlatList,
    TouchableOpacity,
    Alert,
    ScrollView,
    Dimensions,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Avatar } from '@components/common/Avatar';
import { Card } from '@components/common/Card';
import { studentsApi } from '@api/students.api';
import { attendanceApi } from '@api/attendance.api';
import { Student, Class, Stream } from '@types/student.types';
import { SPACING, FONT_SIZES, BORDER_RADIUS, COLORS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import Icon from 'react-native-vector-icons/MaterialIcons';

type AttendanceStatus = 'unmarked' | 'present' | 'absent' | 'late';

interface AttendanceRecord {
    student_id: number;
    status: AttendanceStatus;
}

interface MarkAttendanceScreenProps {
    navigation: any;
}

export const MarkAttendanceScreen: React.FC<MarkAttendanceScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [step, setStep] = useState<'select' | 'mark'>('select');
    const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
    const [classes, setClasses] = useState<Class[]>([]);
    const [selectedClass, setSelectedClass] = useState<Class | null>(null);
    const [streams, setStreams] = useState<Stream[]>([]);
    const [selectedStream, setSelectedStream] = useState<Stream | null>(null);
    const [students, setStudents] = useState<Student[]>([]);
    const [attendance, setAttendance] = useState<AttendanceRecord[]>([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const selectedDateRef = useRef(selectedDate);
    selectedDateRef.current = selectedDate;

    useEffect(() => {
        loadClasses();
    }, []);

    useEffect(() => {
        if (selectedClass) loadStreams(selectedClass.id);
    }, [selectedClass]);

    const loadClasses = async () => {
        try {
            const response = await studentsApi.getClasses();
            if (response.success && response.data) setClasses(response.data);
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load classes');
        }
    };

    const loadStreams = async (classId: number) => {
        try {
            const response = await studentsApi.getStreams(classId);
            if (response.success && response.data) setStreams(response.data);
        } catch {
            setStreams([]);
        }
    };

    const handleSelectClass = (cls: Class) => {
        setSelectedClass(cls);
        setSelectedStream(null);
    };

    const handleSelectStream = (stream: Stream | null) => {
        setSelectedStream(stream);
    };

    const loadMarkingData = useCallback(
        async (dateStr?: string) => {
            if (!selectedClass) return;
            const date = dateStr ?? selectedDateRef.current;
            setLoading(true);
            try {
                const response = await studentsApi.getStudents({
                    class_id: selectedClass.id,
                    stream_id: selectedStream?.id,
                    status: 'active',
                    per_page: 200,
                });
                if (response.success && response.data) {
                    const list = response.data.data;
                    setStudents(list);
                    let rows: AttendanceRecord[] = list.map((s) => ({ student_id: s.id, status: 'unmarked' as AttendanceStatus }));
                    try {
                        const live = await attendanceApi.getClassAttendance(
                            date,
                            selectedClass.id,
                            selectedStream?.id
                        );
                        if (live.success && live.data && Array.isArray(live.data)) {
                            const byId = new Map(
                                live.data.map((r: { student_id: number; status: string }) => [r.student_id, r.status])
                            );
                            rows = list.map((s) => {
                                const st = byId.get(s.id);
                                if (st === 'present' || st === 'absent' || st === 'late') {
                                    return { student_id: s.id, status: st as AttendanceStatus };
                                }
                                return { student_id: s.id, status: 'unmarked' as AttendanceStatus };
                            });
                        }
                    } catch {
                        /* use all unmarked */
                    }
                    setAttendance(rows);
                    setStep('mark');
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load students');
            } finally {
                setLoading(false);
            }
        },
        [selectedClass, selectedStream]
    );

    /** After class/stream is chosen, go straight to marking (no extra “Proceed” step). */
    useEffect(() => {
        if (step !== 'select' || !selectedClass) return;
        const t = setTimeout(() => {
            void loadMarkingData();
        }, 350);
        return () => clearTimeout(t);
    }, [step, selectedClass, selectedStream, streams.length, loadMarkingData]);

    const handleBackToClasses = () => {
        setStep('select');
        setStudents([]);
        setAttendance([]);
    };

    const shiftDate = (delta: number) => {
        const [y, m, d] = selectedDate.split('-').map((n) => parseInt(n, 10));
        const next = new Date(y, m - 1, d);
        next.setDate(next.getDate() + delta);
        const today = new Date();
        today.setHours(23, 59, 59, 999);
        if (next > today) return;
        const yy = next.getFullYear();
        const mm = String(next.getMonth() + 1).padStart(2, '0');
        const dd = String(next.getDate()).padStart(2, '0');
        const nextStr = `${yy}-${mm}-${dd}`;
        setSelectedDate(nextStr);
        if (step === 'mark' && selectedClass) {
            setTimeout(() => void loadMarkingData(nextStr), 0);
        }
    };

    const updateAttendance = (studentId: number, status: AttendanceStatus) => {
        setAttendance((prev) =>
            prev.map((item) => (item.student_id === studentId ? { ...item, status } : item))
        );
    };

    const markAllPresent = () => {
        setAttendance((prev) => prev.map((item) => ({ ...item, status: 'present' as AttendanceStatus })));
    };

    const handleSave = async () => {
        if (!selectedClass) return;
        const hasChanges = attendance.some((a) => a.status !== 'unmarked');
        if (!hasChanges) {
            Alert.alert('No Changes', 'Mark at least one student to save.');
            return;
        }
        setSaving(true);
        try {
            const response = await attendanceApi.markAttendance({
                date: selectedDate,
                class_id: selectedClass.id,
                stream_id: selectedStream?.id,
                records: attendance.map((a) => ({
                    student_id: a.student_id,
                    status: a.status === 'unmarked' ? 'unmarked' : a.status,
                })),
            });
            if (response.success) {
                Alert.alert('Success', response.data?.message || 'Attendance saved.', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to save');
        } finally {
            setSaving(false);
        }
    };

    const getStatusColor = (status: AttendanceStatus) => {
        switch (status) {
            case 'present': return COLORS.present;
            case 'absent': return COLORS.absent;
            case 'late': return COLORS.late;
            default: return isDark ? colors.borderDark : colors.borderLight;
        }
    };

    const markedCount = attendance.filter((a) => a.status !== 'unmarked').length;

    // ————— STEP 1: Class Selection —————
    if (step === 'select') {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <ScrollView contentContainerStyle={styles.selectContent}>
                    <Text style={[styles.sectionLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Select Class
                    </Text>
                    <View style={styles.classGrid}>
                        {classes.map((cls) => (
                            <TouchableOpacity
                                key={cls.id}
                                style={[
                                    styles.classCard,
                                    {
                                        backgroundColor: selectedClass?.id === cls.id ? colors.primary + '20' : isDark ? colors.surfaceDark : colors.surfaceLight,
                                        borderColor: selectedClass?.id === cls.id ? colors.primary : isDark ? colors.borderDark : colors.borderLight,
                                        borderWidth: selectedClass?.id === cls.id ? 2 : 1,
                                    },
                                ]}
                                onPress={() => handleSelectClass(cls)}
                            >
                                <Icon name="class" size={28} color={selectedClass?.id === cls.id ? colors.primary : (isDark ? colors.textSubDark : colors.textSubLight)} />
                                <Text style={[styles.className, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{cls.name}</Text>
                            </TouchableOpacity>
                        ))}
                    </View>

                    {selectedClass && streams.length > 0 && (
                        <>
                            <Text style={[styles.sectionLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Select Stream (optional)
                            </Text>
                            <View style={styles.streamRow}>
                                <TouchableOpacity
                                    style={[
                                        styles.streamChip,
                                        {
                                            backgroundColor: !selectedStream ? colors.primary : isDark ? colors.surfaceDark : colors.surfaceLight,
                                            borderColor: !selectedStream ? colors.primary : isDark ? colors.borderDark : colors.borderLight,
                                        },
                                    ]}
                                    onPress={() => handleSelectStream(null)}
                                >
                                    <Text style={[styles.streamChipText, { color: !selectedStream ? Palette.onPrimary : (isDark ? colors.textMainDark : colors.textMainLight) }]}>
                                        All
                                    </Text>
                                </TouchableOpacity>
                                {streams.map((s) => (
                                    <TouchableOpacity
                                        key={s.id}
                                        style={[
                                            styles.streamChip,
                                            {
                                                backgroundColor: selectedStream?.id === s.id ? colors.primary : isDark ? colors.surfaceDark : colors.surfaceLight,
                                                borderColor: selectedStream?.id === s.id ? colors.primary : isDark ? colors.borderDark : colors.borderLight,
                                            },
                                        ]}
                                        onPress={() => handleSelectStream(s)}
                                    >
                                        <Text style={[styles.streamChipText, { color: selectedStream?.id === s.id ? Palette.onPrimary : (isDark ? colors.textMainDark : colors.textMainLight) }]}>
                                            {s.name}
                                        </Text>
                                    </TouchableOpacity>
                                ))}
                            </View>
                        </>
                    )}

                    {selectedClass && loading ? (
                        <Text style={[styles.loadingHint, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Loading students…
                        </Text>
                    ) : null}
                </ScrollView>
            </SafeAreaView>
        );
    }

    // ————— STEP 2: Mark Students —————
    const renderStudent = ({ item }: { item: Student }) => {
        const rec = attendance.find((a) => a.student_id === item.id);
        const status = rec?.status ?? 'unmarked';

        return (
            <Card style={[styles.studentCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                <View style={styles.studentInfo}>
                    <Avatar name={item.full_name} imageUrl={item.avatar} size={44} />
                    <View style={styles.studentDetails}>
                        <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {item.full_name}
                        </Text>
                        <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.admission_number}
                        </Text>
                    </View>
                </View>

                <View style={styles.statusRow}>
                    {(['present', 'absent', 'late'] as const).map((s) => (
                        <TouchableOpacity
                            key={s}
                            style={[
                                styles.statusBtn,
                                {
                                    backgroundColor: status === s ? getStatusColor(s) : 'transparent',
                                    borderColor: getStatusColor(s),
                                },
                            ]}
                            onPress={() => updateAttendance(item.id, status === s ? 'unmarked' : s)}
                        >
                            <Text style={[styles.statusBtnText, { color: status === s ? Palette.onPrimary : getStatusColor(s) }]}>
                                {s === 'present' ? 'P' : s === 'absent' ? 'A' : 'L'}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>
            </Card>
        );
    };

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View
                style={[
                    styles.dateCard,
                    {
                        marginHorizontal: SPACING.lg,
                        backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                        borderColor: isDark ? colors.borderDark : BRAND.border,
                        borderRadius: RADIUS.card,
                    },
                ]}
            >
                <Text style={[styles.sectionLabel, styles.dateSectionLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    Attendance date
                </Text>
                <View style={styles.dateRow}>
                    <TouchableOpacity
                        style={[styles.dateArrow, { borderColor: BRAND.primary }]}
                        onPress={() => shiftDate(-1)}
                    >
                        <Icon name="chevron-left" size={28} color={BRAND.primary} />
                    </TouchableOpacity>
                    <Text style={[styles.dateText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {selectedDate}
                    </Text>
                    <TouchableOpacity
                        style={[styles.dateArrow, { borderColor: BRAND.primary }]}
                        onPress={() => shiftDate(1)}
                    >
                        <Icon name="chevron-right" size={28} color={BRAND.primary} />
                    </TouchableOpacity>
                </View>
                <Text style={[styles.dateHint, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    Change the date here to mark a different day for this class/stream.
                </Text>
            </View>

            {/* Legend */}
            <View style={[styles.legend, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                <Text style={[styles.legendText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    P=Present  A=Absent  L=Late (tap again to unmark)
                </Text>
            </View>

            {/* Action bar */}
            <View style={styles.actionBar}>
                <Button title="Change Class" onPress={handleBackToClasses} variant="outline" size="small" />
                <Button title="Mark All Present" onPress={markAllPresent} variant="outline" size="small" />
                <Text style={[styles.countText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    {markedCount} marked / {students.length} total
                </Text>
            </View>

            <FlatList
                data={students}
                renderItem={renderStudent}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
            />

            <View style={[styles.footer, { borderTopColor: isDark ? colors.borderDark : colors.borderLight }]}>
                <Button title="Save Attendance" onPress={handleSave} loading={saving} fullWidth />
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    selectContent: { padding: SPACING.xl },
    dateCard: { padding: SPACING.lg, borderWidth: 1, marginBottom: SPACING.md },
    dateRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: SPACING.md },
    dateArrow: { width: 44, height: 44, borderRadius: 22, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
    dateText: { fontSize: FONT_SIZES.lg, fontWeight: '700', minWidth: 120, textAlign: 'center' },
    dateHint: { fontSize: FONT_SIZES.xs, marginTop: SPACING.sm, lineHeight: 18 },
    dateSectionLabel: { marginTop: 0 },
    sectionLabel: { fontSize: FONT_SIZES.sm, fontWeight: '600', marginBottom: SPACING.sm, marginTop: SPACING.lg },
    classGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    classCard: {
        width: (Dimensions.get('window').width - SPACING.xl * 2 - SPACING.md * 2) / 2,
        padding: SPACING.lg,
        borderRadius: BORDER_RADIUS.xl,
        alignItems: 'center',
        gap: SPACING.sm,
    },
    className: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    streamRow: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.sm, marginBottom: SPACING.lg },
    streamChip: {
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
    },
    streamChipText: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    loadingHint: { marginTop: SPACING.lg, textAlign: 'center', fontSize: FONT_SIZES.sm },
    legend: { paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm },
    legendText: { fontSize: FONT_SIZES.xs },
    actionBar: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.md,
    },
    countText: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    listContent: { paddingHorizontal: SPACING.lg, paddingBottom: SPACING.xl },
    studentCard: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: SPACING.md,
        marginBottom: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
    },
    studentInfo: { flexDirection: 'row', alignItems: 'center', flex: 1, gap: SPACING.sm },
    studentDetails: { flex: 1 },
    studentName: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    admissionNumber: { fontSize: FONT_SIZES.xs },
    statusRow: { flexDirection: 'row', gap: 6 },
    statusBtn: {
        width: 40,
        height: 40,
        borderRadius: 20,
        borderWidth: 2,
        alignItems: 'center',
        justifyContent: 'center',
    },
    statusBtnText: { fontSize: FONT_SIZES.xs, fontWeight: 'bold' },
    footer: {
        padding: SPACING.lg,
        borderTopWidth: 1,
    },
});
