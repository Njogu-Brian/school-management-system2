import type { SubjectAnalysisPayload } from '@erp/core';
import React from 'react';
import { ActivityIndicator, Pressable, ScrollView, Text, View } from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';
import { useTheme } from '../theme';
import { AcademicScreenHeader } from './AcademicScreenHeader';

export type SubjectAnalysisDetailViewProps = {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  mode: 'term' | 'exam_type';
  onModeChange: (mode: 'term' | 'exam_type') => void;
  examTypes: Array<{ id: number; name: string }>;
  examTypeId: number | null;
  onExamTypeChange: (id: number) => void;
  data?: SubjectAnalysisPayload | null;
  loading?: boolean;
  error?: string | null;
  onRetry?: () => void;
};

function fmtDelta(v: number | null | undefined): string {
  if (v == null || Number.isNaN(v)) return '—';
  return `${v > 0 ? '+' : ''}${v}`;
}

export const SubjectAnalysisDetailView: React.FC<SubjectAnalysisDetailViewProps> = ({
  title,
  subtitle,
  onBack,
  mode,
  onModeChange,
  examTypes,
  examTypeId,
  onExamTypeChange,
  data,
  loading,
  error,
  onRetry,
}) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const summary = data?.summary;
  const sittings = data?.sittings ?? [];
  const students = data?.students ?? [];
  const prev = data?.meta?.previous_term;

  return (
    <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title={title} subtitle={subtitle} onBack={onBack} />

      <FilterChipRow label="Analysis" wrap>
        <FilterChip label="Whole term" active={mode === 'term'} onPress={() => onModeChange('term')} />
        <FilterChip label="Single exam" active={mode === 'exam_type'} onPress={() => onModeChange('exam_type')} />
      </FilterChipRow>
      {mode === 'exam_type' ? (
        <FilterChipRow label="Exam type" wrap>
          {examTypes.map((et) => (
            <FilterChip
              key={et.id}
              label={et.name}
              active={examTypeId === et.id}
              onPress={() => onExamTypeChange(et.id)}
            />
          ))}
        </FilterChipRow>
      ) : null}

      {loading ? (
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.xl }} />
      ) : error ? (
        <EmptyState title="Could not load" message={error} icon="alert-circle-outline" actionLabel="Retry" onAction={onRetry} />
      ) : !data ? (
        <EmptyState title="Select filters" message="Choose whole term or an exam type." icon="analytics-outline" />
      ) : (
        <>
          {data.meta.notice ? (
            <Text style={{ color: colors.warning ?? palette.textSecondary, marginBottom: spacing.sm }}>
              {data.meta.notice}
            </Text>
          ) : null}
          {prev ? (
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
              Improvement vs {prev.academic_year} · {prev.term_name}
            </Text>
          ) : null}

          <View
            style={{
              flexDirection: 'row',
              flexWrap: 'wrap',
              gap: spacing.sm,
              marginBottom: spacing.md,
            }}
          >
            {[
              { label: 'Mean', value: summary?.mean ?? '—' },
              { label: 'Pass %', value: summary?.pass_rate ?? '—' },
              { label: 'Students', value: summary?.count ?? 0 },
              { label: 'Δ vs prev', value: fmtDelta(summary?.improvement) },
            ].map((card) => (
              <View
                key={card.label}
                style={{
                  width: '47%',
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderWidth: 1,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                }}
              >
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{card.label}</Text>
                <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: 18 }}>{card.value}</Text>
              </View>
            ))}
          </View>

          {sittings.length > 0 ? (
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
              Sittings: {sittings.map((s) => s.label).join(', ')}
            </Text>
          ) : null}

          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              overflow: 'hidden',
            }}
          >
            <View
              style={{
                flexDirection: 'row',
                paddingHorizontal: spacing.sm,
                paddingVertical: spacing.sm,
                borderBottomWidth: 1,
                borderBottomColor: palette.border,
                gap: spacing.xs,
              }}
            >
              <Text style={{ flex: 1.4, fontWeight: '700', color: palette.textSecondary, fontSize: 11 }}>Student</Text>
              {sittings.slice(0, 2).map((s) => (
                <Text key={s.key} style={{ width: 44, fontWeight: '700', color: palette.textSecondary, fontSize: 11, textAlign: 'right' }}>
                  {s.label.slice(0, 3)}
                </Text>
              ))}
              <Text style={{ width: 44, fontWeight: '700', color: palette.textSecondary, fontSize: 11, textAlign: 'right' }}>Avg</Text>
              <Text style={{ width: 40, fontWeight: '700', color: palette.textSecondary, fontSize: 11, textAlign: 'right' }}>Δ</Text>
              <Text style={{ width: 28, fontWeight: '700', color: palette.textSecondary, fontSize: 11, textAlign: 'right' }}>#</Text>
            </View>
            {students.map((row) => (
              <View
                key={row.student_id}
                style={{
                  flexDirection: 'row',
                  paddingHorizontal: spacing.sm,
                  paddingVertical: spacing.sm,
                  borderBottomWidth: 1,
                  borderBottomColor: palette.border,
                  alignItems: 'center',
                  gap: spacing.xs,
                }}
              >
                <View style={{ flex: 1.4 }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: 12 }} numberOfLines={1}>
                    {row.name}
                  </Text>
                  <Text style={{ color: palette.textSecondary, fontSize: 10 }}>{row.admission_number ?? ''}</Text>
                </View>
                {sittings.slice(0, 2).map((s) => (
                  <Text key={s.key} style={{ width: 44, textAlign: 'right', color: palette.textPrimary, fontSize: 12 }}>
                    {row.sitting_scores?.[s.key]?.score ?? '—'}
                  </Text>
                ))}
                <Text style={{ width: 44, textAlign: 'right', color: palette.textPrimary, fontWeight: '700', fontSize: 12 }}>
                  {row.score ?? '—'}
                </Text>
                <Text style={{ width: 40, textAlign: 'right', color: palette.textPrimary, fontSize: 12 }}>
                  {fmtDelta(row.improvement)}
                </Text>
                <Text style={{ width: 28, textAlign: 'right', color: palette.textSecondary, fontSize: 12 }}>
                  {row.position ?? '—'}
                </Text>
              </View>
            ))}
            {students.length === 0 ? (
              <Text style={{ padding: spacing.md, color: palette.textSecondary, textAlign: 'center' }}>
                No student scores for this filter.
              </Text>
            ) : null}
          </View>

          {(data.most_improved_students?.length ?? 0) > 0 ? (
            <View style={{ marginTop: spacing.md }}>
              <Text style={{ fontWeight: '700', color: palette.textPrimary, marginBottom: spacing.sm }}>
                Most improved students
              </Text>
              {data.most_improved_students!.slice(0, 5).map((row) => (
                <Pressable
                  key={`imp-${row.student_id}`}
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    paddingVertical: spacing.xs,
                  }}
                >
                  <Text style={{ color: palette.textPrimary }}>{row.name}</Text>
                  <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{fmtDelta(row.improvement)}</Text>
                </Pressable>
              ))}
            </View>
          ) : null}
        </>
      )}
    </ScrollView>
  );
};
