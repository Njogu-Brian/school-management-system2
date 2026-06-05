import type { MarksListFilters } from '@erp/core';
import { useMemo, useState } from 'react';

export function useMarksRegistryState() {
  const [examId, setExamId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const [classroomId, setClassroomId] = useState<number | null>(null);

  const filters: MarksListFilters | null = useMemo(() => {
    if (examId == null || subjectId == null || classroomId == null) return null;
    return { exam_id: examId, subject_id: subjectId, classroom_id: classroomId };
  }, [examId, subjectId, classroomId]);

  return {
    examId,
    setExamId,
    subjectId,
    setSubjectId,
    classroomId,
    setClassroomId,
    filters,
  };
}
