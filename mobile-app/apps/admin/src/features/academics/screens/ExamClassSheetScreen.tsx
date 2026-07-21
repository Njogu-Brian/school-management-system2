import { useCan, useExamClassSheet, useExamDetail } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'ExamClassSheet'>;

function parseScore(raw: string | number | null | undefined): number | null {
  if (raw == null || raw === '—' || raw === '-') return null;
  const n = typeof raw === 'number' ? raw : parseFloat(String(raw).replace(/[^\d.-]/g, ''));
  return Number.isFinite(n) ? n : null;
}

export const ExamClassSheetScreen: React.FC<Props> = ({ route, navigation }) => {
  const { examId, examSessionId, classroomId, streamId, title } = route.params;
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing, typography } = useTheme();
  const examQuery = useExamDetail(examId ?? 0, { enabled: canView && (examId ?? 0) > 0 });
  const sheetQuery = useExamClassSheet(
    canView
      ? {
          examId,
          examSessionId,
          classroomId,
          streamId,
        }
      : null,
    { enabled: canView },
  );

  const headerTitle = useMemo(() => {
    if (title) return title;
    const sessionName = sheetQuery.data?.meta.exam?.name;
    const examName = sessionName ?? examQuery.data?.name;
    const className = sheetQuery.data?.meta.classroom.name;
    if (examName && className) return `${examName} · ${className}`;
    return examName ?? `Exam results`;
  }, [title, sheetQuery.data, examQuery.data]);

  const grid = useMemo(() => {
    const sheet = sheetQuery.data;
    if (!sheet) return null;
    const { subjects, rows } = sheet;
    const studentCols = rows.map((r) => ({
      id: r.student_id,
      name: r.name.split(' ')[0] ?? r.name,
      fullName: r.name,
      admission: r.admission_number,
      average: r.average,
      position: r.class_position ?? r.position,
    }));
    const subjectRows = subjects.map((sub) => ({
      id: sub.id,
      label: sub.name,
      scores: rows.map((r) => r.subject_scores[String(sub.id)] ?? '—'),
    }));
    const colAverages = studentCols.map((_, ci) => {
      const vals = subjectRows
        .map((sr) => parseScore(sr.scores[ci]))
        .filter((v): v is number => v != null);
      if (!vals.length) return null;
      return Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) * 10) / 10;
    });
    return { studentCols, subjectRows, colAverages };
  }, [sheetQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (sheetQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (sheetQuery.isError || !sheetQuery.data || !grid) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title={headerTitle} onBack={() => navigation.goBack()} />
        <Text style={{ color: colors.error, textAlign: 'center' }}>
          {(sheetQuery.error as Error)?.message ?? 'Could not load class sheet.'}
        </Text>
        <Pressable onPress={() => void sheetQuery.refetch()} style={{ marginTop: spacing.sm, alignSelf: 'center' }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </ScreenContainer>
    );
  }

  const { studentCols, subjectRows, colAverages } = grid;
  const cellW = 56;
  const labelW = 120;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title={headerTitle}
          subtitle="Subjects × students · scroll horizontally"
          onBack={() => navigation.goBack()}
        />

        <ScrollView horizontal showsHorizontalScrollIndicator>
          <View>
            <View style={[styles.headerRow, { borderBottomColor: palette.border }]}>
              <Text style={[styles.labelCell, styles.headerCell, { width: labelW, color: palette.textPrimary }]}>
                Subject
              </Text>
              {studentCols.map((s) => (
                <View key={s.id} style={{ width: cellW, alignItems: 'center' }}>
                  <Text style={[styles.headerCell, { color: palette.textPrimary, fontSize: typography.caption.fontSize }]} numberOfLines={1}>
                    {s.name}
                  </Text>
                </View>
              ))}
            </View>

            {subjectRows.map((sub) => (
              <View key={sub.id} style={[styles.dataRow, { borderBottomColor: palette.border }]}>
                <Text style={[styles.labelCell, { width: labelW, color: palette.textPrimary, fontSize: typography.caption.fontSize }]} numberOfLines={2}>
                  {sub.label}
                </Text>
                {sub.scores.map((score, i) => (
                  <Text
                    key={`${sub.id}-${studentCols[i]?.id ?? i}`}
                    style={{ width: cellW, textAlign: 'center', color: palette.textPrimary, fontSize: typography.caption.fontSize }}
                  >
                    {score}
                  </Text>
                ))}
              </View>
            ))}

            <View style={[styles.dataRow, { borderBottomColor: palette.border, backgroundColor: palette.surfaceRaised }]}>
              <Text style={[styles.labelCell, { width: labelW, fontWeight: '700', color: palette.textPrimary, fontSize: typography.caption.fontSize }]}>
                Average
              </Text>
              {colAverages.map((avg, i) => (
                <Text key={`avg-${studentCols[i]?.id ?? i}`} style={{ width: cellW, textAlign: 'center', fontWeight: '700', color: colors.primary, fontSize: typography.caption.fontSize }}>
                  {avg ?? '—'}
                </Text>
              ))}
            </View>

            <View style={[styles.dataRow, { borderBottomColor: palette.border, backgroundColor: palette.surfaceRaised }]}>
              <Text style={[styles.labelCell, { width: labelW, fontWeight: '700', color: palette.textPrimary, fontSize: typography.caption.fontSize }]}>
                Position
              </Text>
              {studentCols.map((s) => (
                <Text key={`pos-${s.id}`} style={{ width: cellW, textAlign: 'center', fontWeight: '700', color: colors.primary, fontSize: typography.caption.fontSize }}>
                  {s.position ?? '—'}
                </Text>
              ))}
            </View>
          </View>
        </ScrollView>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  headerRow: { flexDirection: 'row', borderBottomWidth: StyleSheet.hairlineWidth, paddingBottom: 8 },
  dataRow: {
    flexDirection: 'row',
    paddingVertical: 8,
    borderBottomWidth: StyleSheet.hairlineWidth,
    alignItems: 'center',
  },
  headerCell: { fontWeight: '700', fontSize: 11 },
  labelCell: { paddingRight: 8 },
});
