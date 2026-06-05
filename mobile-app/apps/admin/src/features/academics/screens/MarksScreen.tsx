import {
  useCan,
  useExamMarkingOptions,
  useInfiniteExams,
  useMarks,
  useSettingsClasses,
} from '@erp/core';
import {
  AcademicScreenHeader,
  MarksRow,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
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
import { useMarksRegistryState } from '../hooks/useMarksRegistryState';

type Props = StackScreenProps<AcademicsStackParamList, 'Marks'>;

export const MarksScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing, fontSizes, radius } = useTheme();
  const { examId, setExamId, subjectId, setSubjectId, classroomId, setClassroomId, filters } =
    useMarksRegistryState();
  const [pickerExamId, setPickerExamId] = useState<number | null>(null);

  const classesQuery = useSettingsClasses({ enabled: canView });
  const examsQuery = useInfiniteExams({ per_page: 50 }, { enabled: canView });
  const exams = useMemo(
    () => examsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [examsQuery.data],
  );
  const optionsQuery = useExamMarkingOptions(pickerExamId ?? 0, {
    enabled: canView && pickerExamId != null,
  });
  const marksQuery = useMarks(filters, { enabled: canView });

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
        data={marksQuery.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={marksQuery.isRefetching}
            onRefresh={() => void marksQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader
              title="Marks"
              subtitle="Class sheet (read-only)"
              onBack={() => navigation.goBack()}
            />
            <Pressable onPress={() => navigation.navigate('MarksMatrix')}>
              <Text style={{ color: colors.primary, fontWeight: '600', marginBottom: spacing.sm }}>
                Open marks matrix →
              </Text>
            </Pressable>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
              Exam
            </Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
              {exams.map((e) => (
                <Pressable
                  key={e.id}
                  onPress={() => {
                    setPickerExamId(e.id);
                    setExamId(e.id);
                    setSubjectId(e.subjectId);
                    setClassroomId(e.classroomId);
                  }}
                  style={[
                    styles.chip,
                    {
                      backgroundColor: examId === e.id ? colors.primary : palette.surface,
                      borderColor: examId === e.id ? colors.primary : palette.border,
                      borderRadius: radius.full,
                      marginRight: spacing.xs,
                    },
                  ]}
                >
                  <Text style={{ color: examId === e.id ? colors.white : palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }} numberOfLines={1}>
                    {e.name}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
            {(optionsQuery.data ?? []).length > 0 ? (
              <>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
                  Class · Subject
                </Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
                  {(optionsQuery.data ?? []).map((o) => (
                    <Pressable
                      key={`${o.classroom_id}-${o.subject_id}`}
                      onPress={() => {
                        setClassroomId(o.classroom_id);
                        setSubjectId(o.subject_id);
                        setExamId(pickerExamId);
                      }}
                      style={[
                        styles.chip,
                        {
                          backgroundColor:
                            classroomId === o.classroom_id && subjectId === o.subject_id
                              ? colors.primary
                              : palette.surface,
                          borderColor:
                            classroomId === o.classroom_id && subjectId === o.subject_id
                              ? colors.primary
                              : palette.border,
                          borderRadius: radius.full,
                          marginRight: spacing.xs,
                        },
                      ]}
                    >
                      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                        {o.classroom_name} · {o.subject_name}
                      </Text>
                    </Pressable>
                  ))}
                </ScrollView>
              </>
            ) : (
              <>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
                  Class
                </Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.sm }}>
                  {(classesQuery.data ?? []).map((c) => (
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
              </>
            )}
            <View style={[styles.headerRow, { borderBottomColor: palette.border, paddingBottom: spacing.xs, marginBottom: spacing.xs }]}>
              <Text style={{ flex: 2, color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>Student</Text>
              <Text style={{ flex: 1, color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700', textAlign: 'right' }}>Score</Text>
              <Text style={{ flex: 1, color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700', textAlign: 'right' }}>%</Text>
            </View>
          </View>
        }
        renderItem={({ item }) => (
          <MarksRow
            row={{
              studentName: item.studentName,
              marks: item.marks,
              totalMarks: item.totalMarks,
              percentage: item.percentage,
              remarks: item.remarks,
            }}
          />
        )}
        ListEmptyComponent={
          !filters ? (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>
              Select exam, class, and subject to load marks.
            </Text>
          ) : marksQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : marksQuery.isError ? (
            <Pressable onPress={() => void marksQuery.refetch()}>
              <Text style={{ color: colors.error }}>{(marksQuery.error as Error).message}</Text>
            </Pressable>
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No marks recorded.</Text>
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderWidth: StyleSheet.hairlineWidth, maxWidth: 200 },
  headerRow: { flexDirection: 'row', borderBottomWidth: StyleSheet.hairlineWidth },
});
