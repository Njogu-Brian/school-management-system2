import React, { useEffect, useMemo, useState } from 'react';
import {
    Alert,
    SafeAreaView,
    ScrollView,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { MarksMatrixExam, MarksMatrixStudent, MarksMatrixExistingMark } from '@types/academics.types';

interface Props {
    navigation: any;
    route: { params: { examTypeId: number; classroomId: number; streamId?: number } };
}

type EntryValue = { marks: string; remarks: string };

export const MarksMatrixEntryScreen: React.FC<Props> = ({ navigation, route }) => {
    const { examTypeId, classroomId, streamId } = route.params;
    const { isDark, colors } = useTheme();

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [students, setStudents] = useState<MarksMatrixStudent[]>([]);
    const [exams, setExams] = useState<MarksMatrixExam[]>([]);
    const [values, setValues] = useState<Record<string, EntryValue>>({});

    const keyOf = (studentId: number, examId: number) => `${studentId}-${examId}`;

    const load = async () => {
        try {
            setLoading(true);
            const res = await academicsApi.getMarksMatrix({
                exam_type_id: examTypeId,
                classroom_id: classroomId,
                stream_id: streamId,
            });
            if (!res.success || !res.data) return;
            setStudents(res.data.students || []);
            setExams(res.data.exams || []);

            const next: Record<string, EntryValue> = {};
            (res.data.existing_marks || []).forEach((m: MarksMatrixExistingMark) => {
                next[keyOf(m.student_id, m.exam_id)] = {
                    marks: m.marks === null || typeof m.marks === 'undefined' ? '' : String(m.marks),
                    remarks: m.remarks || '',
                };
            });
            setValues(next);
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Failed to load mark matrix');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void load();
    }, [examTypeId, classroomId, streamId]);

    const setCell = (studentId: number, examId: number, field: keyof EntryValue, value: string) => {
        const k = keyOf(studentId, examId);
        setValues((prev) => ({
            ...prev,
            [k]: {
                marks: prev[k]?.marks || '',
                remarks: prev[k]?.remarks || '',
                [field]: value,
            },
        }));
    };

    const nonEmptyEntries = useMemo(() => {
        const entries: { student_id: number; exam_id: number; marks?: number; remarks?: string }[] = [];
        students.forEach((s) => {
            exams.forEach((e) => {
                const v = values[keyOf(s.id, e.id)];
                if (!v) return;
                const hasScore = v.marks.trim() !== '';
                const hasRemark = v.remarks.trim() !== '';
                if (!hasScore && !hasRemark) return;
                const marks = hasScore ? Number(v.marks) : undefined;
                if (hasScore && Number.isNaN(marks)) return;
                entries.push({
                    student_id: s.id,
                    exam_id: e.id,
                    marks,
                    remarks: hasRemark ? v.remarks.trim() : undefined,
                });
            });
        });
        return entries;
    }, [students, exams, values]);

    const save = async () => {
        if (nonEmptyEntries.length === 0) {
            Alert.alert('Nothing to save', 'Enter at least one score or remark.');
            return;
        }
        setSaving(true);
        try {
            const res = await academicsApi.enterMarksMatrix({
                exam_type_id: examTypeId,
                classroom_id: classroomId,
                stream_id: streamId,
                entries: nonEmptyEntries,
            });
            if (res.success) {
                Alert.alert('Success', res.data?.message || 'Marks saved.', [
                    { text: 'OK', onPress: () => navigation.goBack() },
                ]);
            }
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Failed to save marks');
        } finally {
            setSaving(false);
        }
    };

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Bulk Marks Entry</Text>
                <View style={{ width: 24 }} />
            </View>

            <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                {students.length} learners · {exams.length} active exams
            </Text>

            <ScrollView contentContainerStyle={styles.content}>
                {loading ? (
                    <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight }}>Loading...</Text>
                ) : exams.length === 0 ? (
                    <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight }}>
                        No open/marking exams found for this context.
                    </Text>
                ) : (
                    students.map((s, idx) => (
                        <Card key={s.id} style={styles.studentCard}>
                            <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {idx + 1}. {s.full_name}
                            </Text>
                            <Text style={[styles.studentMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Adm: {s.admission_number || '—'}
                            </Text>
                            {exams.map((e) => {
                                const k = keyOf(s.id, e.id);
                                const v = values[k] || { marks: '', remarks: '' };
                                return (
                                    <View key={k} style={styles.examRow}>
                                        <View style={styles.examLabelWrap}>
                                            <Text style={[styles.examLabel, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                                {e.subject_name ? `${e.subject_name} · ` : ''}{e.name}
                                            </Text>
                                            <Text style={[styles.examMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                                Min {e.min_marks} / Max {e.max_marks}
                                            </Text>
                                        </View>
                                        <TextInput
                                            style={[
                                                styles.input,
                                                {
                                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                                    color: isDark ? colors.textMainDark : colors.textMainLight,
                                                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                                                },
                                            ]}
                                            value={v.marks}
                                            onChangeText={(t) => setCell(s.id, e.id, 'marks', t)}
                                            keyboardType="numeric"
                                            placeholder="Score"
                                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                                        />
                                        <TextInput
                                            style={[
                                                styles.input,
                                                {
                                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                                    color: isDark ? colors.textMainDark : colors.textMainLight,
                                                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                                                },
                                            ]}
                                            value={v.remarks}
                                            onChangeText={(t) => setCell(s.id, e.id, 'remarks', t)}
                                            placeholder="Remark (optional)"
                                            placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                                        />
                                    </View>
                                );
                            })}
                        </Card>
                    ))
                )}
                <Button title="Save All Entries" onPress={save} loading={saving} fullWidth />
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '700' },
    meta: { paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm },
    content: { paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xxl, gap: SPACING.md },
    studentCard: { marginBottom: SPACING.md },
    studentName: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    studentMeta: { fontSize: FONT_SIZES.xs, marginBottom: SPACING.sm },
    examRow: { marginBottom: SPACING.sm, gap: SPACING.xs },
    examLabelWrap: { marginBottom: 2 },
    examLabel: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    examMeta: { fontSize: FONT_SIZES.xs },
    input: {
        borderWidth: 1,
        borderRadius: 8,
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.xs,
        fontSize: FONT_SIZES.sm,
    },
});
