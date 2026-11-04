import type { TeachingScopeSlot } from '@erp/core';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, Text, View } from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
import { Soft3DIcon } from '../primitives/AccentIcon';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';
import { useTheme } from '../theme';
import { AcademicScreenHeader } from './AcademicScreenHeader';

type Year = { id: number; label?: string | number; year?: string | number; is_active?: boolean };
type Term = { id: number; name: string };

export type SubjectAnalysisHubViewProps = {
  title?: string;
  subtitle?: string;
  onBack?: () => void;
  years: Year[];
  terms: Term[];
  yearId: number | null;
  termId: number | null;
  onYearChange: (id: number) => void;
  onTermChange: (id: number) => void;
  slots: TeachingScopeSlot[];
  fullAccess?: boolean;
  loading?: boolean;
  error?: string | null;
  onRetry?: () => void;
  refreshing?: boolean;
  onRefresh?: () => void;
  onOpenSlot: (slot: TeachingScopeSlot) => void;
  searchHint?: string;
};

export const SubjectAnalysisHubView: React.FC<SubjectAnalysisHubViewProps> = ({
  title = 'Subject analysis',
  subtitle = 'Student scores, averages, and term improvement',
  onBack,
  years,
  terms,
  yearId,
  termId,
  onYearChange,
  onTermChange,
  slots,
  fullAccess,
  loading,
  error,
  onRetry,
  refreshing,
  onRefresh,
  onOpenSlot,
}) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [category, setCategory] = useState<string | 'all'>('all');

  const categories = useMemo(() => {
    const keys = [...new Set(slots.map((s) => s.category_key).filter(Boolean))];
    return keys;
  }, [slots]);

  const filtered = useMemo(() => {
    if (category === 'all') return slots;
    return slots.filter((s) => s.category_key === category);
  }, [slots, category]);

  return (
    <FlatList
      data={filtered}
      keyExtractor={(item) =>
        `${item.classroom_id}:${item.stream_id ?? 0}:${item.subject_id}`
      }
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
      refreshing={refreshing}
      onRefresh={onRefresh}
      ListHeaderComponent={
        <View style={{ marginBottom: spacing.md }}>
          <AcademicScreenHeader title={title} subtitle={subtitle} onBack={onBack} />
          {fullAccess ? (
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
              Admin view — all classes and subjects
            </Text>
          ) : (
            <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
              Showing subjects assigned to you
            </Text>
          )}
          <FilterChipRow label="Academic year" wrap>
            {years.map((y) => (
              <FilterChip
                key={y.id}
                label={`${y.label ?? y.year ?? y.id}${y.is_active ? ' (active)' : ''}`}
                active={yearId === y.id}
                onPress={() => onYearChange(y.id)}
              />
            ))}
          </FilterChipRow>
          <FilterChipRow label="Term" wrap>
            {terms.map((t) => (
              <FilterChip
                key={t.id}
                label={t.name}
                active={termId === t.id}
                onPress={() => onTermChange(t.id)}
              />
            ))}
          </FilterChipRow>
          {categories.length > 1 ? (
            <FilterChipRow label="Section" wrap>
              <FilterChip label="All" active={category === 'all'} onPress={() => setCategory('all')} />
              {categories.map((key) => {
                const label = slots.find((s) => s.category_key === key)?.category ?? key;
                return (
                  <FilterChip
                    key={key}
                    label={label}
                    active={category === key}
                    onPress={() => setCategory(key)}
                  />
                );
              })}
            </FilterChipRow>
          ) : null}
        </View>
      }
      ListEmptyComponent={
        loading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.xl }} />
        ) : error ? (
          <EmptyState title="Could not load" message={error} icon="alert-circle-outline" actionLabel="Retry" onAction={onRetry} />
        ) : (
          <EmptyState
            title="No subjects yet"
            message={yearId && termId ? 'No teaching slots for this year and term.' : 'Pick academic year and term.'}
            icon="book-outline"
          />
        )
      }
      renderItem={({ item }) => (
        <Pressable
          onPress={() => onOpenSlot(item)}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: spacing.md,
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
            marginBottom: spacing.sm,
          }}
        >
          <Soft3DIcon name="exams" tone="indigo" size={44} />
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
              {item.subject_name ?? 'Subject'}
              {item.subject_code ? ` (${item.subject_code})` : ''}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {item.classroom_name}
              {item.stream_name ? ` · ${item.stream_name}` : ''}
              {item.category ? ` · ${item.category}` : ''}
            </Text>
            {item.teacher_name ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                Teacher: {item.teacher_name}
              </Text>
            ) : null}
          </View>
        </Pressable>
      )}
    />
  );
};
