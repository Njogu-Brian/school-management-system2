import { useSaveSpeedTestMarks, useSpeedTest } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, SkeletonListRows, TextField, useTheme } from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useEffect, useState } from 'react';
import { Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'SpeedTestMarks'>;

export const SpeedTestMarksScreen: React.FC = () => {
  const navigation = useNavigation();
  const { batchKey, title } = useRoute<Route>().params;
  const { palette, spacing, typography } = useTheme();
  const detailQuery = useSpeedTest(batchKey);
  const saveMutation = useSaveSpeedTestMarks(batchKey);
  const [scores, setScores] = useState<Record<number, string>>({});

  useEffect(() => {
    const entries = detailQuery.data?.entries ?? [];
    const next: Record<number, string> = {};
    for (const row of entries) {
      next[row.student_id] = row.score != null ? String(row.score) : '';
    }
    setScores(next);
  }, [detailQuery.data]);

  const save = async () => {
    const max = detailQuery.data?.max_marks ?? null;
    const entries = (detailQuery.data?.entries ?? []).map((row) => {
      const raw = scores[row.student_id]?.trim() ?? '';
      let score: number | null = raw === '' ? null : Number(raw);
      if (score != null && (!Number.isFinite(score) || score < 0)) score = null;
      if (score != null && max != null) score = Math.min(score, max);
      return { student_id: row.student_id, score };
    });
    try {
      await saveMutation.mutateAsync(entries);
      showSuccess('Saved', 'Parents can see these speed-test results.');
    } catch (err) {
      showError('Save failed', err instanceof Error ? err.message : 'Try again.');
    }
  };

  const data = detailQuery.data;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title={title || data?.title || 'Speed test marks'}
          subtitle={
            data
              ? `${data.subject_name ?? 'Subject'} · ${data.question_count ?? '—'} questions · out of ${data.max_marks ?? '—'}`
              : undefined
          }
          onBack={() => navigation.goBack()}
        />
        {detailQuery.isLoading ? (
          <SkeletonListRows count={8} />
        ) : (
          <>
            {(data?.entries ?? []).map((row) => (
              <View key={row.student_id} style={{ marginBottom: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '600', marginBottom: 4 }}>
                  {row.student_name}
                </Text>
                <TextField
                  label={`Marks${data?.max_marks != null ? ` / ${data.max_marks}` : ''}`}
                  value={scores[row.student_id] ?? ''}
                  onChangeText={(value) => setScores((prev) => ({ ...prev, [row.student_id]: value }))}
                  keyboardType="decimal-pad"
                />
              </View>
            ))}
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
              Leave blank if the student has not sat the test yet.
            </Text>
            <Button label="Save marks" loading={saveMutation.isPending} onPress={() => void save()} />
          </>
        )}
    </ScreenContainer>
  );
};
