import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    KeyboardAvoidingView,
    Platform,
    Alert,
    TextInput,
    Switch,
} from 'react-native';
import NetInfo from '@react-native-community/netinfo';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { academicsApi } from '@api/academics.api';
import { studentsApi } from '@api/students.api';
import { Exam, Mark, ExamMarkEntryAudit } from 'types/academics.types';
import { Student } from 'types/student.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { Palette } from '@styles/palette';
import {
    queueMarksDraft,
    flushMarksDraftQueue,
    removeMarksDraftQueueItem,
    getPendingMarksQueueCount,
} from '@utils/marksDraftSync';

interface MarksEntryScreenProps {
    navigation: any;
    route: any;
}

export const MarksEntryScreen: React.FC<MarksEntryScreenProps> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const { examId, subjectId, classId } = route.params || {};

    const [exam, setExam] = useState<Exam | null>(null);
    const [students, setStudents] = useState<Student[]>([]);
    const [marks, setMarks] = useState<{ [key: number]: { marks: string; remarks: string; isAbsent: boolean } }>({});
    const [entryAudit, setEntryAudit] = useState<ExamMarkEntryAudit | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [canEdit, setCanEdit] = useState(true);
    const [syncStatus, setSyncStatus] = useState('');
    const [pendingCount, setPendingCount] = useState(0);

    const autosaveTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const draftQueueId = `bulk-${examId}-${classId}`;

    useEffect(() => {
        if (examId && subjectId && classId) loadData();
    }, [examId, subjectId, classId]);

    useEffect(() => {
        const refreshPending = async () => {
            setPendingCount(await getPendingMarksQueueCount());
        };
        void refreshPending();
        void flushMarksDraftQueue().then(() => refreshPending());

        const unsub = NetInfo.addEventListener((state) => {
            if (state.isConnected) {
                void flushMarksDraftQueue().then(() => refreshPending());
            }
        });
        return () => unsub();
    }, []);

    const loadData = async () => {
        if (!examId || !subjectId || !classId) return;
        try {
            setLoading(true);

            const [examRes, studentsRes, marksRes, auditRes] = await Promise.all([
                academicsApi.getExam(examId),
                studentsApi.getStudents({ class_id: classId, per_page: 100 }),
                academicsApi.getMarks({ exam_id: examId, subject_id: subjectId, classroom_id: classId }),
                academicsApi.getExamMarkEntryAudit(examId, classId, subjectId),
            ]);

            if (examRes.success && examRes.data) {
                setExam(examRes.data);
                setCanEdit(examRes.data.can_edit !== false);
            }

            if (studentsRes.success && studentsRes.data) {
                setStudents(studentsRes.data.data);
            }

            if (marksRes.success && marksRes.data) {
                const marksMap: Record<number, { marks: string; remarks: string; isAbsent: boolean }> = {};
                marksRes.data.data.forEach((mark: Mark) => {
                    marksMap[mark.student_id] = {
                        marks: mark.is_absent ? '' : (mark.marks?.toString() ?? ''),
                        remarks: mark.remarks || '',
                        isAbsent: !!mark.is_absent,
                    };
                });
                setMarks(marksMap);
            }

            if (auditRes.success && auditRes.data) {
                setEntryAudit(auditRes.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load data');
        } finally {
            setLoading(false);
        }
    };

    const buildMarksPayload = useCallback(() => {
        return students
            .map((student) => {
                const studentMarks = marks[student.id];
                const hasScore = studentMarks?.marks?.trim();
                const hasRemark = studentMarks?.remarks?.trim();
                const isAbsent = !!studentMarks?.isAbsent;
                if (!hasScore && !hasRemark && !isAbsent) return null;
                return {
                    student_id: student.id,
                    marks: hasScore && !isAbsent ? parseFloat(studentMarks!.marks) : undefined,
                    remarks: hasRemark ? studentMarks!.remarks.trim() : undefined,
                    is_absent: isAbsent,
                };
            })
            .filter((m): m is NonNullable<typeof m> => m !== null);
    }, [students, marks]);

    const persistDraft = useCallback(async () => {
        if (!examId || !subjectId || !classId || !canEdit) return;

        const marksData = buildMarksPayload();
        const payload = {
            exam_id: examId,
            subject_id: subjectId,
            classroom_id: classId,
            marks: marksData,
        };

        await queueMarksDraft({ id: draftQueueId, type: 'bulk', payload });

        const net = await NetInfo.fetch();
        if (!net.isConnected) {
            setSyncStatus('Saved offline — will sync when online');
            setPendingCount(await getPendingMarksQueueCount());
            return;
        }

        try {
            const response = await academicsApi.enterMarks(payload);
            if (response.success) {
                await removeMarksDraftQueueItem(draftQueueId);
                setSyncStatus(`Draft saved · ${new Date().toLocaleTimeString()}`);
            } else {
                setSyncStatus('Saved offline — sync pending');
            }
        } catch {
            setSyncStatus('Saved offline — sync pending');
        }
        setPendingCount(await getPendingMarksQueueCount());
    }, [examId, subjectId, classId, canEdit, buildMarksPayload, draftQueueId]);

    const scheduleAutosave = useCallback(() => {
        if (!canEdit) return;
        if (autosaveTimer.current) clearTimeout(autosaveTimer.current);
        autosaveTimer.current = setTimeout(() => {
            void persistDraft();
        }, 1200);
    }, [canEdit, persistDraft]);

    const handleMarksChange = (studentId: number, value: string) => {
        setMarks((prev) => ({
            ...prev,
            [studentId]: {
                marks: value,
                remarks: prev[studentId]?.remarks || '',
                isAbsent: false,
            },
        }));
        scheduleAutosave();
    };

    const handleRemarksChange = (studentId: number, value: string) => {
        setMarks((prev) => ({
            ...prev,
            [studentId]: {
                marks: prev[studentId]?.marks || '',
                remarks: value,
                isAbsent: prev[studentId]?.isAbsent || false,
            },
        }));
        scheduleAutosave();
    };

    const handleAbsentToggle = (studentId: number, value: boolean) => {
        setMarks((prev) => ({
            ...prev,
            [studentId]: {
                marks: value ? '' : (prev[studentId]?.marks || ''),
                remarks: prev[studentId]?.remarks || '',
                isAbsent: value,
            },
        }));
        scheduleAutosave();
    };

    const validateMarks = (): boolean => {
        const totalMarks = exam?.total_marks || 100;

        for (const student of students) {
            const studentMarks = marks[student.id];
            if (studentMarks?.isAbsent) continue;
            if (studentMarks?.marks) {
                const marksValue = parseFloat(studentMarks.marks);
                if (isNaN(marksValue) || marksValue < 0 || marksValue > totalMarks) {
                    Alert.alert('Validation Error', `Marks for ${student.full_name} must be between 0 and ${totalMarks}`);
                    return false;
                }
            }
        }

        return true;
    };

    const handleSubmitForReview = async () => {
        if (!validateMarks()) return;

        Alert.alert(
            'Submit for review',
            'Submit all marks for this exam? Teachers will not be able to edit after submission.',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Submit',
                    style: 'destructive',
                    onPress: async () => {
                        setSaving(true);
                        try {
                            await persistDraft();
                            const net = await NetInfo.fetch();
                            if (!net.isConnected) {
                                await queueMarksDraft({
                                    id: `submit-${examId}`,
                                    type: 'submit',
                                    payload: { exam_id: examId },
                                });
                                Alert.alert('Queued', 'Submit will complete when you are back online.');
                                return;
                            }
                            const response = await academicsApi.submitExamMarks(examId);
                            if (response.success) {
                                setCanEdit(false);
                                Alert.alert('Submitted', 'Marks submitted for review.', [
                                    { text: 'OK', onPress: () => navigation.goBack() },
                                ]);
                            }
                        } catch (error: any) {
                            Alert.alert('Error', error.message || 'Failed to submit marks');
                        } finally {
                            setSaving(false);
                        }
                    },
                },
            ]
        );
    };

    if (!examId || !subjectId || !classId) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <Text style={[styles.loading, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Missing exam, class, or subject. Go back and try again.
                </Text>
            </SafeAreaView>
        );
    }

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

    const showSubmit = canEdit && exam?.status !== 'moderation';

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <KeyboardAvoidingView
                style={styles.container}
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                keyboardVerticalOffset={0}
            >
                <ScrollView style={styles.content} keyboardShouldPersistTaps="handled">
                    <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {exam?.name} - Total: {exam?.total_marks ?? 100} marks
                        {exam?.status === 'moderation' ? ' · Under review' : ''}
                    </Text>
                    {(syncStatus || pendingCount > 0) && (
                        <Text style={[styles.syncLine, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {syncStatus}
                            {pendingCount > 0 ? ` · ${pendingCount} pending offline` : ''}
                        </Text>
                    )}
                    {entryAudit && (
                        <Card style={styles.auditCard}>
                            <Text style={[styles.auditTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                Submission status
                            </Text>
                            {entryAudit.exam.marking_submitted_at ? (
                                <Text style={[styles.auditLine, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Submitted {new Date(entryAudit.exam.marking_submitted_at).toLocaleString()}
                                    {entryAudit.exam.marking_submitted_by ? ` by ${entryAudit.exam.marking_submitted_by}` : ''}
                                </Text>
                            ) : (
                                <Text style={[styles.auditLine, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    Draft in progress · {entryAudit.counts.submitted} submitted rows · {entryAudit.counts.absent} absent
                                </Text>
                            )}
                        </Card>
                    )}
                    <View style={styles.tableHeader}>
                        <Text style={[styles.columnHeader, styles.studentColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Student
                        </Text>
                        <Text style={[styles.columnHeader, styles.absColumn, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Abs
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

                                <View style={styles.absColumn}>
                                    <Switch
                                        value={!!marks[student.id]?.isAbsent}
                                        onValueChange={(value) => handleAbsentToggle(student.id, value)}
                                        disabled={!canEdit}
                                    />
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
                                        value={marks[student.id]?.isAbsent ? '' : (marks[student.id]?.marks || '')}
                                        onChangeText={(value) => handleMarksChange(student.id, value)}
                                        keyboardType="numeric"
                                        placeholder={marks[student.id]?.isAbsent ? 'ABS' : '0'}
                                        placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                                        maxLength={5}
                                        editable={canEdit && !marks[student.id]?.isAbsent}
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
                                        editable={canEdit}
                                    />
                                </View>
                            </View>
                        </Card>
                    ))}

                    {canEdit && (
                        <Button
                            title="Save draft now"
                            onPress={() => void persistDraft()}
                            loading={saving}
                            variant="outline"
                            fullWidth
                            style={styles.saveButton}
                        />
                    )}
                    {showSubmit && (
                        <Button
                            title="Submit for review"
                            onPress={handleSubmitForReview}
                            loading={saving}
                            fullWidth
                            style={styles.saveButton}
                        />
                    )}
                </ScrollView>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: SPACING.sm,
        marginBottom: SPACING.sm,
        paddingHorizontal: SPACING.xl,
    },
    syncLine: {
        fontSize: FONT_SIZES.xs,
        marginBottom: SPACING.sm,
        paddingHorizontal: SPACING.xl,
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
        borderBottomColor: Palette.borderSlate,
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
    absColumn: {
        width: 52,
        alignItems: 'center',
        justifyContent: 'center',
    },
    auditCard: {
        marginHorizontal: SPACING.xl,
        marginBottom: SPACING.sm,
        padding: SPACING.sm,
    },
    auditTitle: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '700',
        marginBottom: SPACING.xs,
    },
    auditLine: {
        fontSize: FONT_SIZES.xs,
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
