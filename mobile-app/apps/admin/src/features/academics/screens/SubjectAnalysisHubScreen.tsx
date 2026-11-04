import {
  useAcademicContext,
  useCan,
  useMyTeachingScope,
  type TeachingScopeSlot,
} from '@erp/core';
import { EmptyState, ScreenContainer, SubjectAnalysisHubView } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'SubjectAnalysisHub'>;

export const SubjectAnalysisHubScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view');
  const [yearId, setYearId] = useState<number | null>(null);
  const [termId, setTermId] = useState<number | null>(null);

  const academicQuery = useAcademicContext(yearId ?? undefined, { enabled: canView });
  const years = academicQuery.data?.academic_years ?? [];
  const terms = academicQuery.data?.terms ?? [];

  useEffect(() => {
    if (yearId != null) return;
    const active = academicQuery.data?.active_academic_year_id;
    if (active) setYearId(active);
  }, [academicQuery.data?.active_academic_year_id, yearId]);

  useEffect(() => {
    if (termId != null) return;
    const current = academicQuery.data?.current_term_id;
    if (current) setTermId(current);
  }, [academicQuery.data?.current_term_id, termId]);

  const scopeQuery = useMyTeachingScope(
    yearId && termId ? { academic_year_id: yearId, term_id: termId } : null,
    { enabled: canView && yearId != null && termId != null },
  );

  if (!canView) {
    return (
      <ScreenContainer>
        <EmptyState
          title="Access denied"
          message="You need academics.view permission."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  const openSlot = (slot: TeachingScopeSlot) => {
    if (yearId == null || termId == null) return;
    navigation.navigate('SubjectAnalysisDetail', {
      classroomId: slot.classroom_id,
      subjectId: slot.subject_id,
      streamId: slot.stream_id ?? undefined,
      academicYearId: yearId,
      termId,
      classroomName: slot.classroom_name ?? undefined,
      subjectName: slot.subject_name ?? undefined,
      streamName: slot.stream_name ?? undefined,
      teacherName: slot.teacher_name ?? undefined,
    });
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <SubjectAnalysisHubView
        title="Subject analysis"
        subtitle="All classes and subjects — scores, averages, improvement"
        onBack={() => navigation.goBack()}
        years={years}
        terms={terms}
        yearId={yearId}
        termId={termId}
        onYearChange={(id) => {
          setYearId(id);
          setTermId(null);
        }}
        onTermChange={setTermId}
        slots={scopeQuery.data?.slots ?? []}
        fullAccess={scopeQuery.data?.full_access ?? true}
        loading={scopeQuery.isLoading}
        error={scopeQuery.isError ? (scopeQuery.error as Error).message : null}
        onRetry={() => void scopeQuery.refetch()}
        refreshing={scopeQuery.isRefetching}
        onRefresh={() => void scopeQuery.refetch()}
        onOpenSlot={openSlot}
      />
    </ScreenContainer>
  );
};
