import { useEnterMarksMatrix, useMarksMatrix } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'MarksMatrixEntry'>;

type EntryValue = { marks: string; remarks: string };

export const MarksMatrixEntryScreen: React.FC<Props> = ({ navigation, route }) => {
  const { examTypeId, classroomId, streamId } = route.params;
  const { colors, palette, spacing, fontSizes, radius } = useTheme();
  const [values, setValues] = useState<Record<string, EntryValue>>({});
  const [search, setSearch] = useState('');

  const matrixQuery = useMarksMatrix(
    { exam_type_id: examTypeId, classroom_id: classroomId, stream_id: streamId },
    { enabled: true },
  );
  const saveMutation = useEnterMarksMatrix();

  const students = matrixQuery.data?.students ?? [];
  const exams = matrixQuery.data?.exams ?? [];

  const keyOf = (studentId: number, examId: number) => `${studentId}-${examId}`;

  useEffect(() => {
    if (!matrixQuery.data) return;
    const next: Record<string, EntryValue> = {};
    for (const m of matrixQuery.data.existing_marks) {
      next[keyOf(m.student_id, m.exam_id)] = {
        marks: m.marks == null ? '' : String(m.marks),
        remarks: m.remarks ?? '',
      };
    }
    setValues(next);
  }, [matrixQuery.data]);

  const filteredStudents = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return students;
    return students.filter(
      (s) =>
        s.full_name.toLowerCase().includes(q) ||
        (s.admission_number ?? '').toLowerCase().includes(q),
    );
  }, [students, search]);

  const setCell = (studentId: number, examId: number, field: keyof EntryValue, value: string) => {
    const k = keyOf(studentId, examId);
    setValues((prev) => ({
      ...prev,
      [k]: { marks: prev[k]?.marks ?? '', remarks: prev[k]?.remarks ?? '', [field]: value },
    }));
  };

  const nonEmptyEntries = useMemo(() => {
    const entries: { student_id: number; exam_id: number; marks?: number; remarks?: string }[] = [];
    for (const s of students) {
      for (const e of exams) {
        const v = values[keyOf(s.id, e.id)];
        if (!v) continue;
        const hasScore = v.marks.trim() !== '';
        const hasRemark = v.remarks.trim() !== '';
        if (!hasScore && !hasRemark) continue;
        const marks = hasScore ? Number(v.marks) : undefined;
        if (hasScore && Number.isNaN(marks)) continue;
        entries.push({
          student_id: s.id,
          exam_id: e.id,
          marks,
          remarks: hasRemark ? v.remarks.trim() : undefined,
        });
      }
    }
    return entries;
  }, [students, exams, values]);

  const save = async () => {
    if (nonEmptyEntries.length === 0) {
      Alert.alert('Nothing to save', 'Enter at least one score or remark.');
      return;
    }
    try {
      const res = await saveMutation.mutateAsync({
        exam_type_id: examTypeId,
        classroom_id: classroomId,
        stream_id: streamId,
        entries: nonEmptyEntries,
      });
      Alert.alert('Success', res.data?.message ?? 'Marks saved.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (err) {
      Alert.alert('Error', (err as Error).message);
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }} keyboardShouldPersistTaps="handled">
        <AcademicScreenHeader title="Marks matrix entry" subtitle="Students × exams" onBack={() => navigation.goBack()} />

        {matrixQuery.isLoading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : exams.length === 0 ? (
          <Text style={{ color: palette.textSecondary, textAlign: 'center', marginTop: 24 }}>
            No open exams in marking status for this class and exam type.
          </Text>
        ) : (
          <>
            <TextField
              label="Search students"
              value={search}
              onChangeText={setSearch}
              placeholder="Name or admission #"
            />
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
              {filteredStudents.length} student{filteredStudents.length === 1 ? '' : 's'}
            </Text>

            {filteredStudents.map((s, idx) => (
              <View key={s.id} style={[styles.card, { borderColor: palette.border, marginBottom: spacing.md }]}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {idx + 1}. {s.full_name}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
                  Adm: {s.admission_number ?? '—'}
                </Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                  {exams.map((e) => {
                    const k = keyOf(s.id, e.id);
                    const v = values[k] ?? { marks: '', remarks: '' };
                    return (
                      <View key={k} style={[styles.cell, { borderColor: palette.border, backgroundColor: palette.surface, borderRadius: radius.md }]}>
                        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.xs, fontWeight: '700' }} numberOfLines={2}>
                          {e.subject_name ? `${e.subject_name} · ` : ''}
                          {e.name}
                        </Text>
                        <Text style={{ color: palette.textSecondary, fontSize: 10, marginBottom: 4 }}>
                          {e.min_marks}–{e.max_marks}
                        </Text>
                        <TextField
                          label="Score"
                          value={v.marks}
                          onChangeText={(t) => setCell(s.id, e.id, 'marks', t)}
                          keyboardType="numeric"
                        />
                        <TextField
                          label="Remark"
                          value={v.remarks}
                          onChangeText={(t) => setCell(s.id, e.id, 'remarks', t)}
                        />
                      </View>
                    );
                  })}
                </ScrollView>
              </View>
            ))}

            <Button label="Submit marks" onPress={() => void save()} loading={saveMutation.isPending} />
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, padding: 12 },
  cell: { width: 160, borderWidth: StyleSheet.hairlineWidth, padding: 8, marginRight: 8 },
});
