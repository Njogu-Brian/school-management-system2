import { useCan, useCbcSubstrand } from '@erp/core';
import {
  AcademicScreenHeader,
  FinanceFieldSection,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'CbcSubstrand'>;

const BulletList: React.FC<{ items: string[]; color: string }> = ({ items, color }) => (
  <View style={{ gap: 6 }}>
    {items.map((text, idx) => (
      <View key={idx} style={styles.bulletRow}>
        <Text style={{ color }}>•</Text>
        <Text style={{ color, flex: 1, lineHeight: 20 }}>{text}</Text>
      </View>
    ))}
  </View>
);

export const CbcSubstrandScreen: React.FC<Props> = ({ navigation, route }) => {
  const { substrandId, substrandName } = route.params;
  const canView = useCan('academics.view');
  const { palette, spacing, radius, typography } = useTheme();
  const query = useCbcSubstrand(substrandId, { enabled: canView });
  const sub = query.data;

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title={substrandName ?? 'Sub-strand'} onBack={() => navigation.goBack()} />
        <SkeletonListRows count={6} variant="compact" />
      </ScreenContainer>
    );
  }

  if (!sub) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Sub-strand" onBack={() => navigation.goBack()} />
        <ListEmptyState
          icon="git-branch-outline"
          title="Sub-strand not found"
          message="This sub-strand may have been removed."
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      </ScreenContainer>
    );
  }

  const sectionTitle = (label: string) => (
    <Text style={[styles.sectionLabel, { color: palette.textSecondary, marginTop: spacing.md }]}>{label}</Text>
  );

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={sub.name}
        subtitle={[sub.learning_area, sub.strand].filter(Boolean).join(' · ') || undefined}
        onBack={() => navigation.goBack()}
      />

      <FinanceFieldSection
        title="Overview"
        rows={[
          { label: 'Code', value: sub.code ?? '—' },
          { label: 'Strand', value: sub.strand ?? '—' },
          { label: 'Learning area', value: sub.learning_area ?? '—' },
          {
            label: 'Suggested lessons',
            value: sub.suggested_lessons != null ? String(sub.suggested_lessons) : '—',
          },
        ]}
      />

      {sub.description ? (
        <View style={[styles.block, { borderColor: palette.border, marginTop: spacing.md }]}>
          <Text style={{ color: palette.textPrimary, lineHeight: 20 }}>{sub.description}</Text>
        </View>
      ) : null}

      {sub.learning_outcomes.length > 0 ? (
        <>
          {sectionTitle('LEARNING OUTCOMES')}
          <BulletList items={sub.learning_outcomes} color={palette.textPrimary} />
        </>
      ) : null}

      {sub.key_inquiry_questions.length > 0 ? (
        <>
          {sectionTitle('KEY INQUIRY QUESTIONS')}
          <BulletList items={sub.key_inquiry_questions} color={palette.textPrimary} />
        </>
      ) : null}

      {sub.core_competencies.length > 0 ? (
        <>
          {sectionTitle('CORE COMPETENCIES')}
          <BulletList items={sub.core_competencies} color={palette.textPrimary} />
        </>
      ) : null}

      {sub.values.length > 0 ? (
        <>
          {sectionTitle('VALUES')}
          <BulletList items={sub.values} color={palette.textPrimary} />
        </>
      ) : null}

      {sub.competencies.length > 0 ? (
        <>
          {sectionTitle('COMPETENCIES')}
          {sub.competencies.map((comp) => (
            <View
              key={comp.id}
              style={[
                styles.compCard,
                { backgroundColor: palette.surfaceRaised, borderColor: palette.borderSubtle, borderRadius: radius.md },
              ]}
            >
              <View style={styles.compHeader}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }} numberOfLines={2}>
                  {[comp.code, comp.name].filter(Boolean).join(' · ')}
                </Text>
                {comp.competency_level ? <StatusBadge label={comp.competency_level} tone="info" /> : null}
              </View>
              {comp.description ? (
                <Text style={{ color: palette.textSecondary, marginTop: 4, lineHeight: 18, fontSize: typography.caption.fontSize }}>
                  {comp.description}
                </Text>
              ) : null}
              {comp.indicators.length > 0 ? (
                <View style={{ marginTop: 8 }}>
                  <BulletList items={comp.indicators} color={palette.textSecondary} />
                </View>
              ) : null}
            </View>
          ))}
        </>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  sectionLabel: { fontSize: 12, fontWeight: '700', letterSpacing: 0.4, marginBottom: 8 },
  block: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, padding: 14 },
  bulletRow: { flexDirection: 'row', gap: 8 },
  compCard: { borderWidth: StyleSheet.hairlineWidth, padding: 12, marginBottom: 8 },
  compHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
