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
  const { palette, spacing, typography, radius, shadows } = useTheme();

  return (
    <ScrollView
      contentContainerStyle={[
        styles.content,
        { padding: spacing.md, paddingBottom: spacing['5xl'] + spacing.xl },
      ]}
    >
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
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.headline.fontSize,
            fontWeight: typography.headline.fontWeight,
            letterSpacing: typography.headline.letterSpacing,
          }}
        >
          {title}
        </Text>
        {subtitle ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
            }}
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
          <Text
            style={[
              styles.sectionLabel,
              {
                color: palette.textSecondary,
                fontSize: typography.overline.fontSize,
                letterSpacing: typography.overline.letterSpacing,
              },
            ]}
          >
            Summary
          </Text>
          <Text
            style={{
              color: palette.textPrimary,
              fontSize: typography.body.fontSize,
              marginTop: spacing.xs,
            }}
          >
            {summary}
          </Text>
        </View>
      ) : null}

      <View style={{ marginTop: spacing.lg }}>
        <Text
          style={[
            styles.sectionLabel,
            {
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              letterSpacing: typography.overline.letterSpacing,
            },
          ]}
        >
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
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.overline.fontSize,
                fontWeight: '600',
              }}
            >
              {field.label}
            </Text>
            <Text
              style={{
                color: palette.textPrimary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs / 2,
              }}
            >
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
  content: {},
  hero: { borderWidth: StyleSheet.hairlineWidth },
  badges: { flexDirection: 'row', flexWrap: 'wrap' },
  sectionLabel: {
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  fieldRow: { borderBottomWidth: StyleSheet.hairlineWidth },
});
