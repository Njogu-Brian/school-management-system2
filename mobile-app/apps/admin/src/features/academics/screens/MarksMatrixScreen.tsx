import { useCan, useMarksMatrix, useMarksMatrixContext } from '@erp/core';
import { AcademicScreenHeader, Button, ListEmptyState, MarksMatrixRow, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'MarksMatrix'>;

export const MarksMatrixScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing, fontSizes, radius } = useTheme();
  const [examTypeId, setExamTypeId] = useState<number | null>(null);
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);

  const contextQuery = useMarksMatrixContext(classroomId ?? undefined, { enabled: canView });

  useEffect(() => {
    setStreamId(null);
  }, [classroomId]);
  const matrixFilters =
    examTypeId != null && classroomId != null
      ? { exam_type_id: examTypeId, classroom_id: classroomId, stream_id: streamId ?? undefined }
      : null;
  const matrixQuery = useMarksMatrix(matrixFilters, { enabled: canView });

  const rows = useMemo(() => {
    const data = matrixQuery.data;
    if (!data) return [];
    const markMap = new Map<string, number | null>();
    for (const m of data.existing_marks) {
      markMap.set(`${m.student_id}-${m.exam_id}`, m.marks);
    }
    return data.students.map((student) => ({
      student,
      cells: data.exams.map((exam) => ({
        examName: exam.subject_name ?? exam.name,
        score: (() => {
          const v = markMap.get(`${student.id}-${exam.id}`);
          return v == null ? '—' : String(v);
        })(),
      })),
    }));
  }, [matrixQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={rows}
        keyExtractor={(item) => String(item.student.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={matrixQuery.isRefetching}
            onRefresh={() => void matrixQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader
              title="Marks Matrix"
              subtitle="View matrix or enter marks in bulk"
              onBack={() => navigation.goBack()}
            />
            <Button
              label="Bulk marks entry"
              onPress={() => navigation.navigate('MarksMatrixSetup')}
              style={{ marginBottom: spacing.sm }}
            />
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
              Class
            </Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
              {(contextQuery.data?.classrooms ?? []).map((c) => (
                <Pressable
                  key={c.id}
                  onPress={() => setClassroomId(c.id)}
                  style={[
                    styles.chip,
                    {
                      backgroundColor: classroomId === c.id ? colors.primary : palette.surface,
                      borderColor: classroomId === c.id ? colors.primary : palette.border,
                      borderRadius: radius.full,
                      marginRight: spacing.xs,
                    },
                  ]}
                >
                  <Text style={{ color: classroomId === c.id ? colors.white : palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                    {c.name}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
              Exam type
            </Text>
            {classroomId != null && (contextQuery.data?.streams?.length ?? 0) > 0 ? (
              <>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
                  Stream (optional)
                </Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
                  <Pressable
                    onPress={() => setStreamId(null)}
                    style={[
                      styles.chip,
                      {
                        backgroundColor: streamId === null ? colors.primary : palette.surface,
                        borderColor: streamId === null ? colors.primary : palette.border,
                        borderRadius: radius.full,
                        marginRight: spacing.xs,
                      },
                    ]}
                  >
                    <Text style={{ color: streamId === null ? colors.white : palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                      All
                    </Text>
                  </Pressable>
                  {(contextQuery.data?.streams ?? []).map((s) => (
                    <Pressable
                      key={s.id}
                      onPress={() => setStreamId(s.id)}
                      style={[
                        styles.chip,
                        {
                          backgroundColor: streamId === s.id ? colors.primary : palette.surface,
                          borderColor: streamId === s.id ? colors.primary : palette.border,
                          borderRadius: radius.full,
                          marginRight: spacing.xs,
                        },
                      ]}
                    >
                      <Text style={{ color: streamId === s.id ? colors.white : palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                        {s.name}
                      </Text>
                    </Pressable>
                  ))}
                </ScrollView>
              </>
            ) : null}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.md }}>
              {(contextQuery.data?.exam_types ?? []).map((t) => (
                <Pressable
                  key={t.id}
                  onPress={() => setExamTypeId(t.id)}
                  style={[
                    styles.chip,
                    {
                      backgroundColor: examTypeId === t.id ? colors.primary : palette.surface,
                      borderColor: examTypeId === t.id ? colors.primary : palette.border,
                      borderRadius: radius.full,
                      marginRight: spacing.xs,
                    },
                  ]}
                >
                  <Text style={{ color: examTypeId === t.id ? colors.white : palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                    {t.name}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
          </View>
        }
        renderItem={({ item }) => (
          <MarksMatrixRow
            studentName={item.student.full_name}
            admissionNumber={item.student.admission_number}
            cells={item.cells}
          />
        )}
        ListEmptyComponent={
          !matrixFilters ? (
            <ListEmptyState
              title="Select filters"
              message="Choose a class and exam type to load the marks matrix."
              icon="apps-outline"
            />
          ) : matrixQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : matrixQuery.isError ? (
            <ListEmptyState
              title="Could not load matrix"
              message={(matrixQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void matrixQuery.refetch()}
            />
          ) : (
            <ListEmptyState entityName="matrix rows" icon="grid-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderWidth: StyleSheet.hairlineWidth },
});
