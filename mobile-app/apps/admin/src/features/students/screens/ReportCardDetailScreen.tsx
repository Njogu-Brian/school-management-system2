import { useStudentReportCardDetail } from '@erp/core';
import { ScreenContainer } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { formatDateLabel, formatPercent } from '../student360/utils/formatters';

type Props = StackScreenProps<StudentsStackParamList, 'ReportCardDetail'>;

export const ReportCardDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { reportCardId, studentName } = route.params;
  const { colors, spacing, fontSizes, palette, radius } = useTheme();
  const query = useStudentReportCardDetail(reportCardId);

  const BackBar = (
    <Pressable onPress={() => navigation.goBack()} style={[styles.backRow, { padding: spacing.sm }]}>
      <Ionicons name="chevron-back" size={22} color={colors.primary} />
      <Text style={{ color: colors.primary, fontWeight: '600', marginLeft: 4 }}>Back</Text>
    </Pressable>
  );

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        {BackBar}
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (query.isError || !query.data) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        {BackBar}
        <Text style={{ color: colors.error }}>Could not load report card.</Text>
      </ScreenContainer>
    );
  }

  const rc = query.data;
  const position =
    rc.class_position != null
      ? `Class #${rc.class_position}`
      : rc.overall_position != null
        ? `#${rc.overall_position}`
        : null;

  return (
    <ScreenContainer style={styles.flex}>
      {BackBar}
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700' }}>
          {rc.student_name ?? studentName ?? 'Student'}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 4 }}>
          {[rc.class_name, rc.status].filter(Boolean).join(' · ')}
        </Text>

        <View style={[styles.statsRow, { marginTop: spacing.md, gap: spacing.sm }]}>
          <StatBox label="Average" value={formatPercent(rc.overall_percentage)} />
          <StatBox label="Grade" value={rc.overall_grade ?? '—'} />
          {position ? <StatBox label="Position" value={position} /> : null}
        </View>

        {rc.generated_at ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.sm }}>
            Generated {formatDateLabel(rc.generated_at)}
          </Text>
        ) : null}

        <Text
          style={{
            color: palette.textSecondary,
            fontSize: fontSizes.xs,
            fontWeight: '700',
            textTransform: 'uppercase',
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          }}
        >
          Subjects
        </Text>
        {rc.subjects.map((sub) => (
          <View
            key={`${sub.subject_id}-${sub.subject_name}`}
            style={[
              styles.subjectRow,
              {
                borderColor: palette.border,
                borderRadius: radius.md,
                padding: spacing.sm,
                marginBottom: spacing.xs,
              },
            ]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
              {sub.subject_name}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {sub.marks}/{sub.total_marks} · {formatPercent(sub.percentage)} · {sub.grade}
            </Text>
          </View>
        ))}

        {rc.teacher_comment ? (
          <View style={{ marginTop: spacing.md }}>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
              Teacher comment
            </Text>
            <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, marginTop: 4 }}>
              {rc.teacher_comment}
            </Text>
          </View>
        ) : null}

        {rc.principal_comment ? (
          <View style={{ marginTop: spacing.md }}>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '700' }}>
              Principal comment
            </Text>
            <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, marginTop: 4 }}>
              {rc.principal_comment}
            </Text>
          </View>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const StatBox: React.FC<{ label: string; value: string }> = ({ label, value }) => {
  const { palette, fontSizes, radius, spacing } = useTheme();
  return (
    <View
      style={{
        flex: 1,
        backgroundColor: palette.surface,
        borderRadius: radius.md,
        padding: spacing.sm,
        borderWidth: StyleSheet.hairlineWidth,
        borderColor: palette.border,
      }}
    >
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{label}</Text>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700' }}>
        {value}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  backRow: { flexDirection: 'row', alignItems: 'center' },
  statsRow: { flexDirection: 'row', flexWrap: 'wrap' },
  subjectRow: { borderWidth: StyleSheet.hairlineWidth },
});
