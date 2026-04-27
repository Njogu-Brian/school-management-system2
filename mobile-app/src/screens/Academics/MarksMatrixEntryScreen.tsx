import React, { useEffect, useMemo, useState } from 'react';
import {
    Alert,
    ScrollView,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { formatDistanceToNow } from 'date-fns';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { Button } from '@components/common/Button';
import { EmptyState } from '@components/common/EmptyState';
import { ListLoadingSkeleton } from '@components/common/ListLoadingSkeleton';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import type { MarksMatrixExam, MarksMatrixStudent, MarksMatrixExistingMark } from 'types/academics.types';

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
    const [search, setSearch] = useState('');
    const [lastLoadedAt, setLastLoadedAt] = useState<Date | null>(null);

    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;
    const text = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : colors.surfaceLight;
    const border = isDark ? colors.borderDark : colors.borderLight;
    const surfaceMuted = isDark ? colors.accentDark : colors.accentLight;

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
            setLastLoadedAt(new Date());

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

    const filteredStudents = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return students;
        return students.filter(
            (s) =>
                (s.full_name || '').toLowerCase().includes(q) ||
                (s.admission_number || '').toLowerCase().includes(q)
        );
    }, [students, search]);

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

    const syncLabel = lastLoadedAt
        ? `Updated ${formatDistanceToNow(lastLoadedAt, { addSuffix: true })}`
        : 'Not loaded yet';

    return (
        <SafeAreaView style={[styles.safe, { backgroundColor: bg }]} edges={['top']}>
            <View style={[styles.topBar, { backgroundColor: surface, borderBottomColor: border }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.iconBtn}>
                    <Icon name="arrow-back" size={24} color={textSub} />
                </TouchableOpacity>
                <Text style={[styles.brandMark, { color: colors.primary }]} numberOfLines={1}>
                    Marks matrix
                </Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView
                contentContainerStyle={styles.scroll}
                keyboardShouldPersistTaps="handled"
                showsVerticalScrollIndicator={false}
            >
                <View style={styles.titleRow}>
                    <View style={{ flex: 1 }}>
                        <Text style={[styles.h1, { color: text }]}>Marks entry matrix</Text>
                        <Text style={[styles.subtitle, { color: textSub }]}>
                            Enter and verify scores. Save sends all pending cells to the server.
                        </Text>
                    </View>
                    <View style={[styles.syncPill, { borderColor: border, backgroundColor: surfaceMuted }]}>
                        <Icon name="cloud-done" size={16} color={textSub} />
                        <Text style={[styles.syncText, { color: textSub }]} numberOfLines={2}>
                            {syncLabel}
                        </Text>
                    </View>
                </View>

                {loading ? (
                    <ListLoadingSkeleton layout="marks" />
                ) : exams.length === 0 ? (
                    <EmptyState
                        accent="neutral"
                        icon="assignment"
                        title="No exams to enter"
                        message="There are no open exams in marking status for this class and exam type. Adjust setup or try another group."
                        action={
                            <Button title="Go back" variant="outline" onPress={() => navigation.goBack()} />
                        }
                    />
                ) : (
                    <>
                        <View style={[styles.searchWrap, { backgroundColor: surface, borderColor: border }]}>
                            <Icon name="search" size={20} color={textSub} />
                            <TextInput
                                style={[styles.searchInput, { color: text }]}
                                placeholder="Search student name or admission #"
                                placeholderTextColor={textSub}
                                value={search}
                                onChangeText={setSearch}
                            />
                        </View>

                        <View style={styles.countRow}>
                            <Text style={[styles.countBadge, { color: textSub, backgroundColor: surfaceMuted }]}>
                                Total: {filteredStudents.length} student{filteredStudents.length === 1 ? '' : 's'}
                            </Text>
                        </View>

                        {filteredStudents.length === 0 ? (
                            <EmptyState
                                accent="primary"
                                icon="person-search"
                                title="No students found"
                                message="No learners match your search. Try a different name or admission number."
                                action={
                                    <Button title="Clear search" variant="outline" onPress={() => setSearch('')} />
                                }
                            />
                        ) : (
                            filteredStudents.map((s, idx) => (
                                <View
                                    key={s.id}
                                    style={[styles.matrixCard, { backgroundColor: surface, borderColor: border }]}
                                >
                                    <View style={styles.matrixCardHeader}>
                                        <Text style={[styles.studentIndex, { color: textSub }]}>{idx + 1}.</Text>
                                        <View style={{ flex: 1 }}>
                                            <Text style={[styles.studentName, { color: text }]}>{s.full_name}</Text>
                                            <Text style={[styles.adm, { color: textSub }]}>
                                                Adm: {s.admission_number || '—'}
                                            </Text>
                                        </View>
                                    </View>
                                    <ScrollView
                                        horizontal
                                        showsHorizontalScrollIndicator={false}
                                        contentContainerStyle={styles.examScroll}
                                    >
                                        {exams.map((e) => {
                                            const k = keyOf(s.id, e.id);
                                            const v = values[k] || { marks: '', remarks: '' };
                                            return (
                                                <View
                                                    key={k}
                                                    style={[
                                                        styles.examCell,
                                                        { borderColor: border, backgroundColor: bg },
                                                    ]}
                                                >
                                                    <Text style={[styles.examTitle, { color: text }]} numberOfLines={2}>
                                                        {e.subject_name ? `${e.subject_name} · ` : ''}
                                                        {e.name}
                                                    </Text>
                                                    <Text style={[styles.examRange, { color: textSub }]}>
                                                        {e.min_marks}–{e.max_marks}
                                                    </Text>
                                                    <TextInput
                                                        style={[
                                                            styles.scoreInput,
                                                            {
                                                                borderColor: border,
                                                                backgroundColor: surface,
                                                                color: text,
                                                            },
                                                        ]}
                                                        value={v.marks}
                                                        onChangeText={(t) => setCell(s.id, e.id, 'marks', t)}
                                                        keyboardType="numeric"
                                                        placeholder="Score"
                                                        placeholderTextColor={textSub}
                                                    />
                                                    <TextInput
                                                        style={[
                                                            styles.remarkInput,
                                                            {
                                                                borderColor: border,
                                                                backgroundColor: surface,
                                                                color: text,
                                                            },
                                                        ]}
                                                        value={v.remarks}
                                                        onChangeText={(t) => setCell(s.id, e.id, 'remarks', t)}
                                                        placeholder="Remark"
                                                        placeholderTextColor={textSub}
                                                    />
                                                </View>
                                            );
                                        })}
                                    </ScrollView>
                                </View>
                            ))
                        )}

                        <View style={styles.actions}>
                            <Button
                                title="Submit marks"
                                onPress={save}
                                loading={saving}
                                fullWidth
                            />
                        </View>
                    </>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    safe: { flex: 1 },
    topBar: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    iconBtn: {
        width: 40,
        height: 40,
        alignItems: 'center',
        justifyContent: 'center',
    },
    brandMark: {
        fontSize: FONT_SIZES.md,
        fontWeight: '800',
        letterSpacing: -0.3,
    },
    scroll: {
        padding: SPACING.lg,
        paddingBottom: SPACING.xxl * 2,
    },
    titleRow: {
        flexDirection: 'row',
        gap: SPACING.md,
        alignItems: 'flex-start',
        marginBottom: SPACING.lg,
    },
    h1: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '800',
        letterSpacing: -0.5,
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: SPACING.xs,
        lineHeight: 20,
    },
    syncPill: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        paddingHorizontal: SPACING.sm,
        paddingVertical: 6,
        borderRadius: BORDER_RADIUS.full,
        borderWidth: 1,
        maxWidth: 140,
    },
    syncText: {
        fontSize: FONT_SIZES.xs,
        flex: 1,
    },
    searchWrap: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        marginBottom: SPACING.md,
    },
    searchInput: {
        flex: 1,
        fontSize: FONT_SIZES.md,
        paddingVertical: SPACING.xs,
    },
    countRow: {
        marginBottom: SPACING.md,
    },
    countBadge: {
        alignSelf: 'flex-start',
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
        paddingHorizontal: SPACING.sm,
        paddingVertical: 4,
        borderRadius: BORDER_RADIUS.sm,
        overflow: 'hidden',
    },
    matrixCard: {
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.md,
        marginBottom: SPACING.md,
    },
    matrixCardHeader: {
        flexDirection: 'row',
        gap: SPACING.sm,
        marginBottom: SPACING.md,
    },
    studentIndex: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '700',
        width: 24,
    },
    studentName: {
        fontSize: FONT_SIZES.md,
        fontWeight: '700',
    },
    adm: {
        fontSize: FONT_SIZES.xs,
        marginTop: 2,
    },
    examScroll: {
        gap: SPACING.sm,
        paddingRight: SPACING.md,
    },
    examCell: {
        width: 148,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.md,
        padding: SPACING.sm,
    },
    examTitle: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '700',
        minHeight: 32,
    },
    examRange: {
        fontSize: 10,
        marginBottom: SPACING.xs,
    },
    scoreInput: {
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.sm,
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.xs,
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
        marginBottom: SPACING.xs,
    },
    remarkInput: {
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.sm,
        paddingHorizontal: SPACING.sm,
        paddingVertical: SPACING.xs,
        fontSize: FONT_SIZES.xs,
        minHeight: 36,
    },
    actions: {
        marginTop: SPACING.lg,
    },
});
