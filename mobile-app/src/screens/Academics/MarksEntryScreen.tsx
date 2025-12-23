import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    Alert,
    TextInput,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { academicsApi } from '@api/academics.api';
import { studentsApi } from '@api/students.api';
import { Exam, Mark } from '../types/academics.types';
import { Student } from '../types/student.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface MarksEntryScreenProps {
    navigation: any;
    route: any;
}

export const MarksEntryScreen: React.FC<MarksEntryScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { examId, subjectId, classId } = route.params;

    const [exam, setExam] = useState<Exam | null>(null);
    const [students, setStudents] = useState<Student[]>([]);
    const [marks, setMarks] = useState<{ [key: number]: { marks: string; remarks: string } }>({});
    const [existingMarks, setExistingMarks] = useState<Mark[]>([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        loadData();
    }, [examId, subjectId, classId]);

    const loadData = async () => {
        try {
            setLoading(true);

            const [examRes, studentsRes, marksRes] = await Promise.all([
                academicsApi.getExam(examId),
                studentsApi.getStudents({ class_id: classId, per_page: 100 }),
                academicsApi.getMarks({ exam_id: examId, subject_id: subjectId }),
            ]);

            if (examRes.success && examRes.data) {
                setExam(examRes.data);
            }

            if (studentsRes.success && studentsRes.data) {
                setStudents(studentsRes.data.data);
            }

            if (marksRes.success && marksRes.data) {
                setExistingMarks(marksRes.data.data);
                // Pre-fill existing marks
                const marksMap: any = {};
                marksRes.data.data.forEach((mark) => {
                    marksMap[mark.student_id] = {
                        marks: mark.marks.toString(),
                        remarks: mark.remarks || '',
                    };
                });
                setMarks(marksMap);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load data');
        } finally {
            setLoading(false);
        }
    };

    const handleMarksChange = (studentId: number, value: string) => {
        setMarks((prev) => ({
            ...prev,
            [studentId]: {
                ...prev[studentId],
                marks: value,
            },
        }));
    };

    const handleRemarksChange = (studentId: number, value: string) => {
        setMarks((prev) => ({
            ...prev,
            [studentId]: {
                ...prev[studentId],
                remarks: value,
            },
        }));
    };

    const validateMarks = (): boolean => {
        const totalMarks = exam?.total_marks || 100;

        for (const student of students) {
            const studentMarks = marks[student.id];
            if (studentMarks && studentMarks.marks) {
                const marksValue = parseFloat(studentMarks.marks);
                if (isNaN(marksValue) || marksValue < 0 || marksValue > totalMarks) {
                    Alert.alert('Validation Error', `Marks for ${student.full_name} must be between 0 and ${totalMarks}`);
                    return false;
                }
            }
        }

        return true;
    };

    const handleSave = async () => {
        if (!validateMarks()) return;

        setSaving(true);
        try {
            const marksData = students
                .map((student) => {
                    const studentMarks = marks[student.id];
                    if (studentMarks && studentMarks.marks) {
                        return {
                            student_id: student.id,
                            marks: parseFloat(studentMarks.marks),
                            remarks: studentMarks.remarks || undefined,
                        };
                    }
                    return null;
                })
                .filter((m) => m !== null);

            if (marksData.length === 0) {
                Alert.alert('Error', 'Please enter marks for at least one student');
                return;
            }

            const response = await academicsApi.enterMarks({
                exam_id: examId,
                subject_id: subjectId,
                marks: marksData as any,
            });

            if (response.success) {
                Alert.alert('Success', `Marks saved successfully for ${marksData.length} students`, [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to save marks');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
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
                <View style={styles.headerInfo}>
                    <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Enter Marks
                    </Text>
                    <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {exam?.name} - Total: {exam?.total_marks || 100} marks
                    </Text>
                </View>
                <View style={{ width: 24 }} />
            </View>

            {/* Students List */}
            <ScrollView style={styles.content}>
                <View style={styles.tableHeader}>
                    <Text style={[styles.columnHeader, styles.studentColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Student
                    </Text>
                    <Text style={[styles.columnHeader, styles.marksColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Marks
                    </Text>
                    <Text style={[styles.columnHeader, styles.remarksColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Remarks
                    </Text>
                </View>

                {students.map((student, index) => (
                    <Card key={student.id} style={styles.studentCard}>
                        <View style={styles.studentRow}>
                            <View style={[styles.studentColumn, styles.studentInfo]}>
                                <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {index + 1}. {student.full_name}
                                </Text>
                                <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {student.admission_number}
                                </Text>
                            </View>

                            <View style={styles.marksColumn}>
                                <TextInput
                                    style={[
                                        styles.input,
                                        {
                                            backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                            color: isDark ? colors.textMainDark : colors.textMainLight,
                                            borderColor: isDark ? colors.borderDark : colors.borderLight,
                                        },
                                    ]}
                                    value={marks[student.id]?.marks || ''}
                                    onChangeText={(value) => handleMarksChange(student.id, value)}
                                    keyboardType="numeric"
                                    placeholder="0"
                                    placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                                    maxLength={5}
                                />
                            </View>

                            <View style={styles.remarksColumn}>
                                <TextInput
                                    style={[
                                        styles.input,
                                        {
                                            backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                            color: isDark ? colors.textMainDark : colors.textMainLight,
                                            borderColor: isDark ? colors.borderDark : colors.borderLight,
                                        },
                                    ]}
                                    value={marks[student.id]?.remarks || ''}
                                    onChangeText={(value) => handleRemarksChange(student.id, value)}
                                    placeholder="Optional"
                                    placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                                    maxLength={100}
                                />
                            </View>
                        </View>
                    </Card>
                ))}

                <Button
                    title="Save Marks"
                    onPress={handleSave}
                    loading={saving}
                    fullWidth
                    style={styles.saveButton}
                />
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    headerInfo: {
        flex: 1,
        marginLeft: SPACING.md,
    },
    title: {
        fontSize: FONT_SIZES.xl,
        fontWeight: 'bold',
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: 2,
    },
    loading: {
        textAlign: 'center',
        marginTop: SPACING.xxl,
    },
    content: {
        flex: 1,
    },
    tableHeader: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    columnHeader: {
        fontSize: FONT_SIZES.sm,
        fontWeight: 'bold',
    },
    studentColumn: {
        flex: 2,
    },
    marksColumn: {
        flex: 1,
        alignItems: 'center',
    },
    remarksColumn: {
        flex: 1.5,
    },
    studentCard: {
        marginHorizontal: SPACING.xl,
        marginVertical: SPACING.xs,
        padding: SPACING.sm,
    },
    studentRow: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    studentInfo: {
        justifyContent: 'center',
    },
    studentName: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    admissionNumber: {
        fontSize: FONT_SIZES.xs,
    },
    input: {
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.xs,
        fontSize: FONT_SIZES.sm,
        textAlign: 'center',
    },
    saveButton: {
        margin: SPACING.xl,
    },
});
