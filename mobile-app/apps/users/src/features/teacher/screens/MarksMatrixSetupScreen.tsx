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
  const [selectedExamType, setSelectedExamType] = useState<number | null>(null);
  const [selectedClassroom, setSelectedClassroom] = useState<number | null>(null);
  const [selectedStream, setSelectedStream] = useState<number | null>(null);
  const [selectedExamIds, setSelectedExamIds] = useState<number[]>([]);

  const contextQuery = useMarksMatrixContext(selectedClassroom ?? undefined);
  const examTypes = contextQuery.data?.exam_types ?? [];
  const classrooms = contextQuery.data?.classrooms ?? [];
  const streams = contextQuery.data?.streams ?? [];

  const matrixPreview = useMarksMatrix(
    selectedExamType && selectedClassroom
      ? {
          exam_type_id: selectedExamType,
          classroom_id: selectedClassroom,
          stream_id: selectedStream ?? undefined,
        }
      : null,
    { enabled: Boolean(selectedExamType && selectedClassroom) },
  );
  const availableExams = matrixPreview.data?.exams ?? [];

  useEffect(() => {
    setSelectedStream(null);
  }, [selectedClassroom]);

  useEffect(() => {
    setSelectedExamIds([]);
  }, [selectedExamType, selectedClassroom, selectedStream]);

  useEffect(() => {
    if (availableExams.length === 0) return;
    // Auto-select all authorized subjects when the list first loads (or when only one).
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
  const selectedStreamName = useMemo(() => {
    if (!selectedStream) return 'All streams';
    return streams.find((s) => s.id === selectedStream)?.name ?? 'All streams';
  }, [streams, selectedStream]);

  const toggleExam = (id: number) => {
    setSelectedExamIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const handleContinue = () => {
    if (!selectedExamType || !selectedClassroom) {
      showError('Select context', 'Please select exam type and class.');
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
      examTypeName: selectedExamTypeName,
      classroomName: selectedClassroomName,
      streamName: selectedStream ? selectedStreamName : undefined,
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
        subtitle="Exam · class · subjects you teach"
        onBack={() => navigation.goBack()}
      />

      <View style={[styles.summary, { borderColor: palette.border, padding: spacing.md, marginBottom: spacing.md }]}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>Selected</Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Exam: {selectedExamTypeName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Class: {selectedClassroomName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Subjects: {selectedExamIds.length || '—'}
        </Text>
      </View>

      <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>1) Exam type</Text>
      <View style={styles.grid}>
        {examTypes.map((t) => (
          <Pressable key={t.id} onPress={() => setSelectedExamType(t.id)} style={[styles.pill, pill(selectedExamType === t.id)]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
              {t.name}
            </Text>
          </Pressable>
        ))}
      </View>

      <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>2) Class</Text>
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
          <Text style={[styles.sectionTitle, { color: palette.textPrimary }]}>3) Stream (optional)</Text>
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

      {selectedExamType && selectedClassroom ? (
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
              No open exams in marking for this class and exam type.
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
  summary: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12 },
  sectionTitle: { fontWeight: '700', marginBottom: 8, marginTop: 8 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 8 },
  pill: { borderWidth: 1, paddingVertical: 8, paddingHorizontal: 12 },
});
