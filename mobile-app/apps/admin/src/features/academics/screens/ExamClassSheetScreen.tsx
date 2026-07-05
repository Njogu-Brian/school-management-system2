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

export const ExamClassSheetScreen: React.FC<Props> = ({ route, navigation }) => {
  const { examId, classroomId, streamId, title } = route.params;
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const examQuery = useExamDetail(examId, { enabled: canView });
  const sheetQuery = useExamClassSheet(
    canView ? { examId, classroomId, streamId } : null,
    { enabled: canView },
  );

  const headerTitle = useMemo(() => {
    if (title) return title;
    const examName = sheetQuery.data?.meta.exam?.name ?? examQuery.data?.name;
    const className = sheetQuery.data?.meta.classroom.name;
    if (examName && className) return `${examName} · ${className}`;
    return examName ?? `Exam #${examId}`;
  }, [title, sheetQuery.data, examQuery.data, examId]);

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

  if (sheetQuery.isError || !sheetQuery.data) {
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

  const { subjects, rows } = sheetQuery.data;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title={headerTitle}
          subtitle="Combined results — all subjects"
          onBack={() => navigation.goBack()}
        />

        <ScrollView horizontal showsHorizontalScrollIndicator>
          <View>
            <View style={[styles.headerRow, { borderBottomColor: palette.border }]}>
              <Text style={[styles.cellName, styles.headerCell, { color: palette.textPrimary }]}>#</Text>
              <Text style={[styles.cellName, styles.headerCell, { color: palette.textPrimary }]}>Student</Text>
              {subjects.map((sub) => (
                <Text
                  key={sub.id}
                  style={[styles.cellScore, styles.headerCell, { color: palette.textPrimary }]}
                  numberOfLines={1}
                >
                  {sub.code ?? sub.name}
                </Text>
              ))}
              <Text style={[styles.cellTotal, styles.headerCell, { color: palette.textPrimary }]}>Total</Text>
              <Text style={[styles.cellTotal, styles.headerCell, { color: palette.textPrimary }]}>Avg</Text>
              <Text style={[styles.cellPos, styles.headerCell, { color: palette.textPrimary }]}>Pos</Text>
            </View>

            {rows.map((row, index) => (
              <View key={row.student_id} style={[styles.dataRow, { borderBottomColor: palette.border }]}>
                <Text style={[styles.cellName, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
                  {index + 1}
                </Text>
                <View style={styles.cellName}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.xs }}>
                    {row.name}
                  </Text>
                  <Text style={{ color: palette.textSecondary, fontSize: 10 }}>{row.admission_number}</Text>
                </View>
                {subjects.map((sub) => (
                  <Text
                    key={sub.id}
                    style={[styles.cellScore, { color: palette.textPrimary, fontSize: fontSizes.xs }]}
                  >
                    {row.subject_scores[String(sub.id)] ?? '—'}
                  </Text>
                ))}
                <Text style={[styles.cellTotal, { color: palette.textPrimary, fontSize: fontSizes.xs }]}>
                  {row.total ?? '—'}
                </Text>
                <Text style={[styles.cellTotal, { color: palette.textPrimary, fontSize: fontSizes.xs }]}>
                  {row.average ?? '—'}
                </Text>
                <Text style={[styles.cellPos, { color: colors.primary, fontWeight: '700', fontSize: fontSizes.xs }]}>
                  {row.class_position ?? row.position ?? '—'}
                </Text>
              </View>
            ))}
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
  cellName: { width: 120, paddingRight: 8 },
  cellScore: { width: 44, textAlign: 'center' },
  cellTotal: { width: 52, textAlign: 'center' },
  cellPos: { width: 36, textAlign: 'center' },
});
