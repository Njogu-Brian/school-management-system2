import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { ApprovalPriorityBadge } from './ApprovalPriorityBadge';
import { ApprovalStatusBadge } from './ApprovalStatusBadge';
import type { ApprovalPriority, ApprovalStatus } from './types';

export interface ApprovalDetailField {
  label: string;
  value: string;
}

export interface ApprovalDetailViewProps {
  title: string;
  subtitle?: string;
  status: ApprovalStatus;
  priority: ApprovalPriority;
  fields: ApprovalDetailField[];
  summary?: string;
  children?: React.ReactNode;
}

export const ApprovalDetailView: React.FC<ApprovalDetailViewProps> = ({
  title,
  subtitle,
  status,
  priority,
  fields,
  summary,
  children,
}) => {
  const { palette, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <ScrollView contentContainerStyle={[styles.content, { padding: spacing.md }]}>
      <View
        style={[
          styles.hero,
          {
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderRadius: radius.lg,
            padding: spacing.md,
          },
          shadows.sm,
        ]}
      >
        <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
          {title}
        </Text>
        {subtitle ? (
          <Text
            style={[styles.subtitle, { color: palette.textSecondary, fontSize: fontSizes.sm }]}
          >
            {subtitle}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.sm, gap: spacing.xs }]}>
          <ApprovalStatusBadge status={status} />
          <ApprovalPriorityBadge priority={priority} />
        </View>
      </View>

      {summary ? (
        <View style={{ marginTop: spacing.md }}>
          <Text style={[styles.sectionLabel, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
            Summary
          </Text>
          <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, marginTop: 4 }}>
            {summary}
          </Text>
        </View>
      ) : null}

      <View style={{ marginTop: spacing.lg }}>
        <Text style={[styles.sectionLabel, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
          Details
        </Text>
        {fields.map((field) => (
          <View
            key={field.label}
            style={[
              styles.fieldRow,
              { borderBottomColor: palette.border, paddingVertical: spacing.sm },
            ]}
          >
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
              {field.label}
            </Text>
            <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, marginTop: 2 }}>
              {field.value}
            </Text>
          </View>
        ))}
      </View>

      {children}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  content: { paddingBottom: 120 },
  hero: { borderWidth: StyleSheet.hairlineWidth },
  title: { fontWeight: '700' },
  subtitle: { marginTop: 4 },
  badges: { flexDirection: 'row', flexWrap: 'wrap' },
  sectionLabel: {
    fontWeight: '700',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  fieldRow: { borderBottomWidth: StyleSheet.hairlineWidth },
});
