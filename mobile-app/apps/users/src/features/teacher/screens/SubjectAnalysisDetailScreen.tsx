import { useMarksMatrixContext, useMySubjectAnalysis } from '@erp/core';
import { ScreenContainer, SubjectAnalysisDetailView } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Props = StackScreenProps<TeacherStackParamList, 'SubjectAnalysisDetail'>;

export const SubjectAnalysisDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const {
    classroomId,
    subjectId,
    streamId,
    academicYearId,
    termId,
    classroomName,
    subjectName,
    streamName,
  } = route.params;

  const [mode, setMode] = useState<'term' | 'exam_type'>('term');
  const [examTypeId, setExamTypeId] = useState<number | null>(null);

  const ctxQuery = useMarksMatrixContext(classroomId, academicYearId);
  const examTypes = useMemo(() => {
    const raw = ctxQuery.data?.exam_types ?? [];
    return raw
      .filter((et) => {
        const hay = `${et.name ?? ''} ${et.code ?? ''}`.toLowerCase();
        return !hay.includes('knec') && !/\b(cba|sba)\b/.test(hay);
      })
      .map((et) => ({ id: et.id, name: et.name }));
  }, [ctxQuery.data?.exam_types]);

  const analysisQuery = useMySubjectAnalysis(
    {
      classroom_id: classroomId,
      subject_id: subjectId,
      academic_year_id: academicYearId,
      term_id: termId,
      mode,
      exam_type_id: mode === 'exam_type' ? examTypeId ?? undefined : undefined,
      stream_id: streamId,
    },
    {
      enabled:
        mode === 'term' || (mode === 'exam_type' && examTypeId != null),
    },
  );

  const title = subjectName ?? 'Subject analysis';
  const subtitle = [classroomName, streamName].filter(Boolean).join(' · ');

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <SubjectAnalysisDetailView
        title={title}
        subtitle={subtitle || undefined}
        onBack={() => navigation.goBack()}
        mode={mode}
        onModeChange={(m) => {
          setMode(m);
          if (m === 'exam_type' && examTypeId == null && examTypes[0]) {
            setExamTypeId(examTypes[0].id);
          }
        }}
        examTypes={examTypes}
        examTypeId={examTypeId}
        onExamTypeChange={setExamTypeId}
        data={analysisQuery.data}
        loading={analysisQuery.isLoading || analysisQuery.isFetching}
        error={analysisQuery.isError ? (analysisQuery.error as Error).message : null}
        onRetry={() => void analysisQuery.refetch()}
      />
    </ScreenContainer>
  );
};
