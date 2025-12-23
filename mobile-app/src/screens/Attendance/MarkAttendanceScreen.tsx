import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    FlatList,
    TouchableOpacity,
    Alert,
    ScrollView,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Avatar } from '@components/common/Avatar';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { studentsApi } from '@api/students.api';
import { attendanceApi } from '@api/attendance.api';
import { Student, Class, Stream } from '@types/student.types';
import { SPACING, FONT_SIZES, BORDER_RADIUS, COLORS } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface AttendanceStatus {
    student_id: number;
    status: 'present' | 'absent' | 'late' | 'excused';
}

interface MarkAttendanceScreenProps {
    navigation: any;
}

export const MarkAttendanceScreen: React.FC<MarkAttendanceScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
    const [classes, setClasses] = useState<Class[]>([]);
    const [selectedClass, setSelectedClass] = useState<Class | null>(null);
    const [streams, setStreams] = useState<Stream[]>([]);
    const [selectedStream, setSelectedStream] = useState<Stream | null>(null);
    const [students, setStudents] = useState<Student[]>([]);
    const [attendance, setAttendance] = useState<AttendanceStatus[]>([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    // Load classes on mount
    useEffect(() => {
        loadClasses();
    }, []);

    // Load streams when class is selected
    useEffect(() => {
        if (selectedClass) {
            loadStreams(selectedClass.id);
        }
    }, [selectedClass]);

    // Load students when class/stream is selected
    useEffect(() => {
        if (selectedClass) {
            loadStudents();
        }
    }, [selectedClass, selectedStream]);

    const loadClasses = async () => {
        try {
            const response = await studentsApi.getClasses();
            if (response.success && response.data) {
                setClasses(response.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load classes');
        }
    };

    const loadStreams = async (classId: number) => {
        try {
            const response = await studentsApi.getStreams(classId);
            if (response.success && response.data) {
                setStreams(response.data);
            }
        } catch (error: any) {
            console.error('Failed to load streams:', error);
            setStreams([]);
        }
    };

    const loadStudents = async () => {
        setLoading(true);
        try {
            const response = await studentsApi.getStudents({
                class_id: selectedClass?.id,
                stream_id: selectedStream?.id,
                status: 'active',
                per_page: 100,
            });

            if (response.success && response.data) {
                setStudents(response.data.data);

                // Initialize all as present
                setAttendance(
                    response.data.data.map((student) => ({
                        student_id: student.id,
                        status: 'present',
                    }))
                );
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load students');
        } finally {
            setLoading(false);
        }
    };

    const updateAttendance = (studentId: number, status: 'present' | 'absent' | 'late' | 'excused') => {
        setAttendance((prev) =>
            prev.map((item) =>
                item.student_id === studentId ? { ...item, status } : item
            )
        );
    };

    const markAllPresent = () => {
        setAttendance((prev) =>
            prev.map((item) => ({ ...item, status: 'present' }))
        );
    };

    const handleSave = async () => {
        if (!selectedClass) {
            Alert.alert('Error', 'Please select a class');
            return;
        }

        setSaving(true);
        try {
            const response = await attendanceApi.markAttendance({
                date: selectedDate,
                class_id: selectedClass.id,
                stream_id: selectedStream?.id,
                records: attendance,
            });

            if (response.success) {
                Alert.alert('Success', response.data?.message || 'Attendance marked successfully', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to save attendance');
        } finally {
            setSaving(false);
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'present':
                return COLORS.present;
            case 'absent':
                return COLORS.absent;
            case 'late':
                return COLORS.late;
            case 'excused':
                return COLORS.excused;
            default:
                return colors.primary;
        }
    };

    const renderStudent = ({ item }: { item: Student }) => {
        const studentAttendance = attendance.find((a) => a.student_id === item.id);
        const status = studentAttendance?.status || 'present';

        return (
            <Card style={styles.studentCard}>
                <View style={styles.studentInfo}>
                    <Avatar name={item.full_name} imageUrl={item.avatar} size={40} />
                    <View style={styles.studentDetails}>
                        <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {item.full_name}
                        </Text>
                        <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.admission_number}
                        </Text>
                    </View>
                </View>

                <View style={styles.statusButtons}>
                    {['present', 'absent', 'late', 'excused'].map((s) => (
                        <TouchableOpacity
                            key={s}
                            style={[
                                styles.statusButton,
                                {
                                    backgroundColor: status === s ? getStatusColor(s) : 'transparent',
                                    borderColor: getStatusColor(s),
                                },
                            ]}
                            onPress={() => updateAttendance(item.id, s as any)}
                        >
                            <Text
                                style={[
                                    styles.statusText,
                                    {
                                        color: status === s ? '#ffffff' : getStatusColor(s),
                                    },
                                ]}
                            >
                                {s[0].toUpperCase()}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>
            </Card>
        );
    };

    if (loading) {
        return (
            <SafeAreaView
                style={[
                    styles.container,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <LoadingState message="Loading students..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
            ]}
        >
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Mark Attendance
                </Text>
                <View style={{ width: 24 }} />
            </View>

            {/* Selectors */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.selectors}>
                {/* Class Selector */}
                <View style={styles.selector}>
                    <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Class
                    </Text>
                    <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                        {classes.map((cls) => (
                            <TouchableOpacity
                                key={cls.id}
                                style={[
                                    styles.chip,
                                    {
                                        backgroundColor: selectedClass?.id === cls.id ? colors.primary : isDark ? colors.surfaceDark : colors.surfaceLight,
                                        borderColor: isDark ? colors.borderDark : colors.borderLight,
                                    },
                                ]}
                                onPress={() => {
                                    setSelectedClass(cls);
                                    setSelectedStream(null);
                                }}
                            >
                                <Text
                                    style={[
                                        styles.chipText,
                                        {
                                            color: selectedClass?.id === cls.id ? '#ffffff' : isDark ? colors.textMainDark : colors.textMainLight,
                                        },
                                    ]}
                                >
                                    {cls.name}
                                </Text>
                            </TouchableOpacity>
                        ))}
                    </ScrollView>
                </View>

                {/* Stream Selector (if streams exist) */}
                {streams.length > 0 && (
                    <View style={styles.selector}>
                        <Text style={[styles.label, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Stream
                        </Text>
                        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                            {streams.map((stream) => (
                                <TouchableOpacity
                                    key={stream.id}
                                    style={[
                                        styles.chip,
                                        {
                                            backgroundColor: selectedStream?.id === stream.id ? colors.primary : isDark ? colors.surfaceDark : colors.surfaceLight,
                                            borderColor: isDark ? colors.borderDark : colors.borderLight,
                                        },
                                    ]}
                                    onPress={() => setSelectedStream(stream)}
                                >
                                    <Text
                                        style={[
                                            styles.chipText,
                                            {
                                                color: selectedStream?.id === stream.id ? '#ffffff' : isDark ? colors.textMainDark : colors.textMainLight,
                                            },
                                        ]}
                                    >
                                        {stream.name}
                                    </Text>
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    </View>
                )}
            </ScrollView>

            {/* Action Bar */}
            {students.length > 0 && (
                <View style={styles.actionBar}>
                    <Button title="Mark All Present" onPress={markAllPresent} variant="outline" size="small" />
                    <Text style={[styles.countText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {attendance.filter((a) => a.status === 'present').length}/{students.length} Present
                    </Text>
                </View>
            )}

            {/* Students List */}
            <FlatList
                data={students}
                renderItem={renderStudent}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
            />

            {/* Save Button */}
            {students.length > 0 && (
                <View style={styles.footer}>
                    <Button
                        title="Save Attendance"
                        onPress={handleSave}
                        loading={saving}
                        fullWidth
                    />
                </View>
            )}
        </SafeAreaView>
    );
};

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
    selectors: {
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.md,
    },
    selector: {
        marginRight: SPACING.lg,
    },
    label: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '500',
        marginBottom: SPACING.xs,
    },
    chip: {
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        marginRight: SPACING.sm,
    },
    chipText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    actionBar: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.sm,
    },
    countText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
    },
    studentCard: {
        flexDirection: 'row',
        justifyContentalignItems: 'center',
        gap: SPACING.md,
    },
    studentInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        flex: 1,
        gap: SPACING.sm,
    },
    studentDetails: {
        flex: 1,
    },
    studentName: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    admissionNumber: {
        fontSize: FONT_SIZES.xs,
    },
    statusButtons: {
        flexDirection: 'row',
        gap: 4,
    },
    statusButton: {
        width: 32,
        height: 32,
        borderRadius: 16,
        borderWidth: 2,
        alignItems: 'center',
        justifyContent: 'center',
    },
    statusText: {
        fontSize: FONT_SIZES.xs,
        fontWeight: 'bold',
    },
    footer: {
        padding: SPACING.xl,
        borderTopWidth: 1,
        borderTopColor: '#e2e8f0',
    },
});
