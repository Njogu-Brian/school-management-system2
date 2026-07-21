import { useGradingSettings } from '@erp/core';
import { EmptyState, SettingCard, SettingsSectionHeader, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';

export const GradingSettingsSection: React.FC = () => {
  const { colors, spacing, palette, typography } = useTheme();
  const query = useGradingSettings();

  const schemeCards = useMemo(() => {
    const data = query.data;
    if (!data) return [];
    return data.schemes.map((scheme) => ({
      id: `scheme-${scheme.id}`,
      label: scheme.name + (scheme.is_default ? ' (default)' : ''),
      value: `${scheme.bands.length} band(s) · ${scheme.type ?? 'general'}`,
      hint: scheme.bands
        .slice(0, 3)
        .map((b) => `${b.label ?? '—'} ${b.min ?? ''}-${b.max ?? ''}`)
        .join(' · '),
    }));
  }, [query.data]);

  const examTypeCards = useMemo(() => {
    const types = query.data?.exam_types ?? [];
    return types.slice(0, 8).map((t) => ({
      id: `exam-${t.id}`,
      label: t.name,
      value: t.code ?? '—',
      hint:
        t.default_min_mark != null && t.default_max_mark != null
          ? `Default ${t.default_min_mark}–${t.default_max_mark}`
          : undefined,
    }));
  }, [query.data]);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Could not load grading settings"
        message={(query.error as Error)?.message ?? 'Try again in a moment.'}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }

  return (
    <View style={{ gap: spacing.sm }}>
      <SettingsSectionHeader
        title="Grading"
        subtitle="Grading schemes, bands, and exam types (read-only)."
      />
      <SettingCard
        id="schemes-count"
        label="Grading schemes"
        value={String(query.data?.schemes.length ?? 0)}
      />
      {schemeCards.map((c) => (
        <SettingCard key={c.id} id={c.id} label={c.label} value={c.value} hint={c.hint} />
      ))}

      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.overline.fontSize,
          lineHeight: typography.overline.lineHeight,
          fontWeight: typography.overline.fontWeight,
          letterSpacing: typography.overline.letterSpacing,
          textTransform: 'uppercase',
          marginTop: spacing.md,
        }}
      >
        Exam types
      </Text>
      <SettingCard
        id="exam-types-count"
        label="Exam types"
        value={String(query.data?.exam_types.length ?? 0)}
      />
      {examTypeCards.map((c) => (
        <SettingCard key={c.id} id={c.id} label={c.label} value={c.value} hint={c.hint} />
      ))}
    </View>
  );
};
