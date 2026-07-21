import {
  useAcademicYearsSettings,
  useSettingsClasses,
  useSettingsStreams,
  useSettingsSubjects,
  useTermsSettings,
} from '@erp/core';
import { EmptyState, SettingCard, SettingsSectionHeader, useTheme } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';

export const AcademicSettingsSection: React.FC = () => {
  const { colors, spacing, palette, typography } = useTheme();
  const yearsQuery = useAcademicYearsSettings();
  const termsQuery = useTermsSettings();
  const classesQuery = useSettingsClasses();
  const subjectsQuery = useSettingsSubjects();

  const [selectedClassId, setSelectedClassId] = useState<number | null>(null);
  const streamsQuery = useSettingsStreams(selectedClassId, {
    enabled: selectedClassId != null,
  });

  const summaryCards = useMemo(() => {
    const years = yearsQuery.data ?? [];
    const terms = termsQuery.data ?? [];
    const classes = classesQuery.data ?? [];
    const subjects = subjectsQuery.data ?? [];
    const activeYear = years.find((y) => y.is_active);
    const currentTerm = terms.find((t) => t.is_current);

    return [
      {
        id: 'years',
        label: 'Academic years',
        value: String(years.length),
        hint: activeYear ? `Active: ${activeYear.label}` : undefined,
      },
      {
        id: 'terms',
        label: 'Terms',
        value: String(terms.length),
        hint: currentTerm ? `Current: ${currentTerm.name}` : undefined,
      },
      { id: 'classes', label: 'Classes', value: String(classes.length) },
      {
        id: 'subjects',
        label: 'Subjects',
        value: String(subjects.length),
        hint: `${subjects.filter((s) => s.is_active).length} active`,
      },
    ];
  }, [yearsQuery.data, termsQuery.data, classesQuery.data, subjectsQuery.data]);

  const isLoading =
    yearsQuery.isLoading ||
    termsQuery.isLoading ||
    classesQuery.isLoading ||
    subjectsQuery.isLoading;

  const isError =
    yearsQuery.isError ||
    termsQuery.isError ||
    classesQuery.isError ||
    subjectsQuery.isError;

  if (isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (isError) {
    return (
      <EmptyState
        title="Could not load academic settings"
        message="Check your connection and try again."
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => {
          void yearsQuery.refetch();
          void termsQuery.refetch();
          void classesQuery.refetch();
          void subjectsQuery.refetch();
        }}
      />
    );
  }

  const classes = classesQuery.data ?? [];

  return (
    <View style={{ gap: spacing.sm }}>
      <SettingsSectionHeader
        title="Academic"
        subtitle="Calendar, classes, streams, and subjects."
      />
      {summaryCards.map((c) => (
        <SettingCard key={c.id} id={c.id} label={c.label} value={c.value} hint={c.hint} />
      ))}

      <Text
        style={{
          color: palette.textMuted,
          fontWeight: typography.overline.fontWeight,
          fontSize: typography.overline.fontSize,
          lineHeight: typography.overline.lineHeight,
          letterSpacing: typography.overline.letterSpacing,
          marginTop: spacing.md,
          textTransform: 'uppercase',
        }}
      >
        Classes & streams
      </Text>
      {classes.slice(0, 12).map((c) => (
        <Pressable key={c.id} onPress={() => setSelectedClassId(c.id)}>
          <SettingCard
            label={c.name}
            value={
              selectedClassId === c.id
                ? `${streamsQuery.data?.length ?? 0} stream(s) · selected`
                : 'Tap to view streams'
            }
          />
        </Pressable>
      ))}
      {classes.length > 12 ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
          }}
        >
          +{classes.length - 12} more classes on web portal
        </Text>
      ) : null}
    </View>
  );
};
