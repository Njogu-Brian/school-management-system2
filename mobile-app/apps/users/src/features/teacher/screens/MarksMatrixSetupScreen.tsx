import { useMarksMatrixContext } from '@erp/core';
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

  const contextQuery = useMarksMatrixContext(selectedClassroom ?? undefined);
  const examTypes = contextQuery.data?.exam_types ?? [];
  const classrooms = contextQuery.data?.classrooms ?? [];
  const streams = contextQuery.data?.streams ?? [];

  useEffect(() => {
    setSelectedStream(null);
  }, [selectedClassroom]);

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

  const handleContinue = () => {
    if (!selectedExamType || !selectedClassroom) {
      showError('Select context', 'Please select exam type and class.');
      return;
    }
    navigation.navigate('MarksMatrixEntry', {
      examTypeId: selectedExamType,
      classroomId: selectedClassroom,
      streamId: selectedStream ?? undefined,
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
        subtitle="Class · exam type · stream"
        onBack={() => navigation.goBack()}
      />

      <View style={[styles.summary, { borderColor: palette.border, padding: spacing.md, marginBottom: spacing.md }]}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>Selected context</Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Exam type: {selectedExamTypeName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Class: {selectedClassroomName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Stream: {selectedStreamName}
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

      <Button
        label={contextQuery.isLoading ? 'Loading…' : 'Load bulk entry'}
        onPress={handleContinue}
        loading={contextQuery.isLoading}
        style={{ marginTop: spacing.lg }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  summary: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12 },
  sectionTitle: { fontWeight: '700', marginBottom: 8, marginTop: 8 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 8 },
  pill: { borderWidth: 1, paddingVertical: 6, paddingHorizontal: 12 },
});
