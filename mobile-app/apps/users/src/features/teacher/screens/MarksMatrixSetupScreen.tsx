import { useMarksMatrix, useMarksMatrixContext } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const MarksMatrixSetupScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [selectedYear, setSelectedYear] = useState<number | null>(null);
  const [selectedTerm, setSelectedTerm] = useState<number | null>(null);
  const [selectedExamType, setSelectedExamType] = useState<number | null>(null);
  const [selectedClassroom, setSelectedClassroom] = useState<number | null>(null);
  const [selectedStream, setSelectedStream] = useState<number | null>(null);
  const [selectedExamIds, setSelectedExamIds] = useState<number[]>([]);

  const contextQuery = useMarksMatrixContext(selectedClassroom ?? undefined, selectedYear ?? undefined);
  const examTypes = contextQuery.data?.exam_types ?? [];
  const classrooms = contextQuery.data?.classrooms ?? [];
  const streams = contextQuery.data?.streams ?? [];
  const years = contextQuery.data?.academic_years ?? [];
  const terms = contextQuery.data?.terms ?? [];

  useEffect(() => {
    if (selectedYear != null) return;
    const active = contextQuery.data?.active_academic_year_id;
    if (active) setSelectedYear(active);
  }, [contextQuery.data?.active_academic_year_id, selectedYear]);

  useEffect(() => {
    if (selectedTerm != null) return;
    const current = contextQuery.data?.current_term_id;
    if (current) setSelectedTerm(current);
  }, [contextQuery.data?.current_term_id, selectedTerm]);

  const matrixPreview = useMarksMatrix(
    selectedExamType && selectedClassroom
      ? {
          exam_type_id: selectedExamType,
          classroom_id: selectedClassroom,
          stream_id: selectedStream ?? undefined,
          academic_year_id: selectedYear ?? undefined,
          term_id: selectedTerm ?? undefined,
        }
      : null,
    { enabled: Boolean(selectedExamType && selectedClassroom && selectedYear && selectedTerm) },
  );
  const availableExams = matrixPreview.data?.exams ?? [];

  useEffect(() => {
    setSelectedStream(null);
  }, [selectedClassroom]);

  useEffect(() => {
    setSelectedExamIds([]);
  }, [selectedExamType, selectedClassroom, selectedStream, selectedYear, selectedTerm]);

  useEffect(() => {
    if (availableExams.length === 0) return;
    setSelectedExamIds((prev) => {
      if (prev.length > 0) {
        const allowed = new Set(availableExams.map((e) => e.id));
        const kept = prev.filter((id) => allowed.has(id));
        return kept.length > 0 ? kept : availableExams.map((e) => e.id);
      }
      return availableExams.map((e) => e.id);
    });
  }, [availableExams]);

  const selectedExamTypeName = useMemo(
    () => examTypes.find((e) => e.id === selectedExamType)?.name ?? 'Not selected',
    [examTypes, selectedExamType],
  );
  const selectedClassroomName = useMemo(
    () => classrooms.find((c) => c.id === selectedClassroom)?.name ?? 'Not selected',
    [classrooms, selectedClassroom],
  );
  const selectedYearName = useMemo(() => {
    const y = years.find((x) => x.id === selectedYear);
    return y ? String(y.label ?? y.year ?? y.id) : 'Not selected';
  }, [years, selectedYear]);
  const selectedTermName = useMemo(
    () => terms.find((t) => t.id === selectedTerm)?.name ?? 'Not selected',
    [terms, selectedTerm],
  );

  const toggleExam = (id: number) => {
    setSelectedExamIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const handleContinue = () => {
    if (!selectedYear || !selectedTerm || !selectedExamType || !selectedClassroom) {
      showError('Select context', 'Please select academic year, term, exam type and class.');
      return;
    }
    if (selectedExamIds.length === 0) {
      showError('Select subjects', 'Choose at least one subject you are authorized to mark.');
      return;
    }
    navigation.navigate('MarksMatrixEntry', {
      examTypeId: selectedExamType,
      classroomId: selectedClassroom,
      streamId: selectedStream ?? undefined,
      academicYearId: selectedYear,
      termId: selectedTerm,
      examTypeName: selectedExamTypeName,
      classroomName: selectedClassroomName,
      streamName: selectedStream
        ? streams.find((s) => s.id === selectedStream)?.name
        : undefined,
      academicYearName: selectedYearName,
      termName: selectedTermName,
      selectedExamIds,
    });
  };

  const pill = (active: boolean) => ({
    borderColor: active ? colors.primary : palette.border,
    backgroundColor: active ? `${colors.primary}22` : palette.surface,
    borderRadius: radius.full,
  });

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Bulk marks setup"
        subtitle="Year · term · exam · class · subjects"
        onBack={() => navigation.goBack()}
      />

      <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>Academic year</Text>
      <View style={styles.grid}>
        {years.map((y) => (
          <Pressable
            key={y.id}
            onPress={() => {
              setSelectedYear(y.id);
              setSelectedTerm(null);
            }}
            style={[styles.pill, pill(selectedYear === y.id)]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
              {String(y.label ?? y.year ?? y.id)}
              {y.is_active ? ' (active)' : ''}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>Term</Text>
      <View style={styles.grid}>
        {terms.map((t) => (
          <Pressable key={t.id} onPress={() => setSelectedTerm(t.id)} style={[styles.pill, pill(selectedTerm === t.id)]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
              {t.name}
              {t.is_current ? ' (current)' : ''}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>Exam type</Text>
      <View style={styles.grid}>
        {examTypes.map((t) => (
          <Pressable key={t.id} onPress={() => setSelectedExamType(t.id)} style={[styles.pill, pill(selectedExamType === t.id)]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
              {t.name}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>Class</Text>
      <View style={styles.grid}>
        {classrooms.map((c) => (
          <Pressable
            key={c.id}
            onPress={() => setSelectedClassroom(c.id)}
            style={[styles.pill, pill(selectedClassroom === c.id)]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
              {c.name}
            </Text>
          </Pressable>
        ))}
      </View>

      {selectedClassroom && streams.length > 0 ? (
        <>
          <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>Stream (optional)</Text>
          <View style={styles.grid}>
            <Pressable onPress={() => setSelectedStream(null)} style={[styles.pill, pill(selectedStream === null)]}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
                All streams
              </Text>
            </Pressable>
            {streams.map((s) => (
              <Pressable
                key={s.id}
                onPress={() => setSelectedStream(s.id)}
                style={[styles.pill, pill(selectedStream === s.id)]}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
                  {s.name}
                </Text>
              </Pressable>
            ))}
          </View>
        </>
      ) : null}

      {selectedExamType && selectedClassroom && selectedYear && selectedTerm ? (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 8 }}>
            <Text style={[styles.sectionTitle, { color: palette.textPrimary, marginTop: 0, marginBottom: 0 }]}>
              Subjects
            </Text>
            <View style={{ flexDirection: 'row', gap: 12 }}>
              <Pressable onPress={() => setSelectedExamIds(availableExams.map((e) => e.id))} disabled={availableExams.length === 0}>
                <Text style={{ color: colors.primary, fontWeight: '600' }}>Select all</Text>
              </Pressable>
              <Pressable onPress={() => setSelectedExamIds([])}>
                <Text style={{ color: palette.textSecondary, fontWeight: '600' }}>Clear</Text>
              </Pressable>
            </View>
          </View>
          {matrixPreview.isLoading ? (
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>Loading subjects…</Text>
          ) : availableExams.length === 0 ? (
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
              No open exams in marking for this year, term, class and exam type.
            </Text>
          ) : (
            <View style={styles.grid}>
              {availableExams.map((e) => {
                const active = selectedExamIds.includes(e.id);
                return (
                  <Pressable key={e.id} onPress={() => toggleExam(e.id)} style={[styles.pill, pill(active)]}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
                      {active ? '☑ ' : '☐ '}
                      {e.subject_name || e.name}
                      <Text style={{ color: palette.textSecondary, fontWeight: '400' }}> / {e.max_marks}</Text>
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          )}
        </>
      ) : null}

      <Button
        label={contextQuery.isLoading || matrixPreview.isFetching ? 'Loading…' : 'Continue'}
        onPress={handleContinue}
        loading={contextQuery.isLoading || matrixPreview.isFetching}
        style={{ marginTop: spacing.lg }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  sectionTitle: { fontWeight: '700', marginBottom: 8, marginTop: 8 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 8 },
  pill: { borderWidth: 1, paddingVertical: 8, paddingHorizontal: 12 },
});
