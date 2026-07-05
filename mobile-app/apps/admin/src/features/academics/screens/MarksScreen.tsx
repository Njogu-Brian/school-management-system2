import {
  useAcademicYearsSettings,
  useCan,
  useExamSessions,
  useSettingsClasses,
  useSettingsStreams,
  useTermsSettings,
} from '@erp/core';
import { AcademicScreenHeader, Button, FilterChip, FilterChipRow, ListEmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { sessionDisplayLabel } from '../utils/examLabels';

type Props = StackScreenProps<AcademicsStackParamList, 'Marks'>;

export const MarksScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing, fontSizes } = useTheme();

  const yearsQuery = useAcademicYearsSettings({ enabled: canView });
  const [yearId, setYearId] = useState<number | null>(null);
  const termsQuery = useTermsSettings(yearId ?? undefined, { enabled: canView && yearId != null });
  const [termId, setTermId] = useState<number | null>(null);
  const classesQuery = useSettingsClasses({ enabled: canView });
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const streamsQuery = useSettingsStreams(classroomId, { enabled: canView && classroomId != null });
  const [streamId, setStreamId] = useState<number | null>(null);

  useEffect(() => {
    const years = yearsQuery.data ?? [];
    if (!yearId && years.length) {
      const current = years.find((y) => y.is_active) ?? years[0];
      setYearId(current.id);
    }
  }, [yearsQuery.data, yearId]);

  useEffect(() => {
    const terms = termsQuery.data ?? [];
    if (termId && !terms.some((t) => t.id === termId)) {
      setTermId(null);
    }
    if (!termId && terms.length) {
      const current = terms.find((t) => t.is_current) ?? terms[0];
      setTermId(current.id);
    }
  }, [termsQuery.data, termId]);

  const sessionsQuery = useExamSessions(
    {
      academic_year_id: yearId ?? undefined,
      term_id: termId ?? undefined,
      classroom_id: classroomId ?? undefined,
      stream_id: streamId ?? undefined,
    },
    { enabled: canView && yearId != null && termId != null && classroomId != null },
  );

  const [sessionId, setSessionId] = useState<number | null>(null);

  const sessions = useMemo(() => {
    const list = sessionsQuery.data ?? [];
    const byLabel = new Map<string, (typeof list)[0]>();
    for (const s of list) {
      const label = sessionDisplayLabel(s);
      if (!byLabel.has(label)) {
        byLabel.set(label, s);
      }
    }
    return Array.from(byLabel.entries()).map(([label, session]) => ({ label, session }));
  }, [sessionsQuery.data]);

  useEffect(() => {
    if (sessionId && !sessions.some((s) => s.session.id === sessionId)) {
      setSessionId(null);
    }
  }, [sessions, sessionId]);

  const selectedSession = sessions.find((s) => s.session.id === sessionId)?.session;

  const openGrid = () => {
    if (!classroomId || !selectedSession) return;
    navigation.navigate('ExamClassSheet', {
      examSessionId: selectedSession.id,
      classroomId,
      streamId: streamId ?? undefined,
      title: `${sessionDisplayLabel(selectedSession)} · ${selectedSession.classroom_name ?? 'Class'}`,
    });
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Exam results" subtitle="Class mark grid" onBack={() => navigation.goBack()} />

        <FilterChipRow label="Academic year">
          {(yearsQuery.data ?? []).map((y) => (
            <FilterChip
              key={y.id}
              label={String(y.year)}
              active={yearId === y.id}
              onPress={() => {
                setYearId(y.id);
                setTermId(null);
              }}
            />
          ))}
        </FilterChipRow>

        <FilterChipRow label="Term">
          {(termsQuery.data ?? []).map((t) => (
            <FilterChip key={t.id} label={t.name} active={termId === t.id} onPress={() => setTermId(t.id)} />
          ))}
        </FilterChipRow>

        <FilterChipRow label="Class">
          {(classesQuery.data ?? []).map((c) => (
            <FilterChip
              key={c.id}
              label={c.name}
              active={classroomId === c.id}
              onPress={() => {
                setClassroomId(c.id);
                setStreamId(null);
                setSessionId(null);
              }}
            />
          ))}
        </FilterChipRow>

        {(streamsQuery.data ?? []).length > 0 ? (
          <FilterChipRow label="Stream">
            <FilterChip label="All" active={streamId == null} onPress={() => setStreamId(null)} />
            {(streamsQuery.data ?? []).map((s) => (
              <FilterChip key={s.id} label={s.name} active={streamId === s.id} onPress={() => setStreamId(s.id)} />
            ))}
          </FilterChipRow>
        ) : null}

        {classroomId && termId ? (
          <>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.sm, marginBottom: spacing.xs }}>
              Exam
            </Text>
            {sessionsQuery.isLoading ? (
              <ActivityIndicator color={colors.primary} />
            ) : sessions.length === 0 ? (
              <ListEmptyState title="No exams" message="No exam sessions for this class and term." icon="document-outline" />
            ) : (
              <FilterChipRow label="">
                {sessions.map(({ label, session }) => (
                  <FilterChip
                    key={session.id}
                    label={label}
                    active={sessionId === session.id}
                    onPress={() => setSessionId(session.id)}
                  />
                ))}
              </FilterChipRow>
            )}
          </>
        ) : (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: spacing.md }}>
            Select year, term, and class to load exams.
          </Text>
        )}

        {selectedSession && classroomId ? (
          <Button label="View class mark grid" onPress={openGrid} style={{ marginTop: spacing.lg }} />
        ) : null}

        <Text
          onPress={() => navigation.navigate('MarksMatrix')}
          style={{ color: colors.primary, fontWeight: '600', marginTop: spacing.lg, textAlign: 'center' }}
        >
          Open marks entry matrix →
        </Text>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
