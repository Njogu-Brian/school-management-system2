import { buildStudentTimeline, type StudentDetail, type StudentStatementRecord } from '@erp/core';
import {
  Soft3DIcon,
  StaffFieldSection,
  StudentSummaryWidgets,
  StudentTimeline,
  useTheme,
  type StudentSummaryWidgetData,
  type StudentTimelineEventData,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { openPhoneActions } from '../../../../utils/contactActions';
import { formatDateLabel, formatKes, formatPercent } from '../utils/formatters';

export interface OverviewTabProps {
  student: StudentDetail;
  attendancePct: number | null | undefined;
  feeBalance: number | null | undefined;
  canViewFees: boolean;
  statementLoading: boolean;
  statement?: StudentStatementRecord | null;
}

function StatusChip({
  label,
  tone,
}: {
  label: string;
  tone: 'neutral' | 'success' | 'warning' | 'danger';
}) {
  const { palette, typography, radius, spacing, semantic } = useTheme();
  const colors =
    tone === 'success'
      ? { bg: semantic.success.bg, fg: semantic.success.fg }
      : tone === 'warning'
        ? { bg: semantic.warning.bg, fg: semantic.warning.fg }
        : tone === 'danger'
          ? { bg: semantic.danger.bg, fg: semantic.danger.fg }
          : { bg: palette.surfaceMuted, fg: palette.textSecondary };

  return (
    <View
      style={{
        backgroundColor: colors.bg,
        borderRadius: radius.full,
        paddingHorizontal: spacing.sm,
        paddingVertical: 4,
      }}
    >
      <Text
        style={{
          color: colors.fg,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
        }}
      >
        {label}
      </Text>
    </View>
  );
}

function ContactMiniCard({
  label,
  name,
  phone,
}: {
  label: string;
  name?: string | null;
  phone?: string | null;
}) {
  const { palette, typography, spacing, radius, elevation } = useTheme();
  if (!name && !phone) return null;

  return (
    <View
      style={[
        elevation[1],
        {
          flex: 1,
          minWidth: '46%',
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderWidth: StyleSheet.hairlineWidth,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: 0.4,
        }}
      >
        {label}
      </Text>
      <Text
        style={{
          color: palette.textMain,
          fontSize: typography.body.fontSize,
          fontWeight: '700',
          marginTop: 4,
        }}
        numberOfLines={2}
      >
        {name ?? '—'}
      </Text>
      {phone ? (
        <Pressable
          onPress={() => void openPhoneActions(phone, name ?? label)}
          style={{ flexDirection: 'row', alignItems: 'center', marginTop: spacing.sm, gap: 6 }}
        >
          <Ionicons name="call-outline" size={14} color={palette.primary} />
          <Text style={{ color: palette.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
            {phone}
          </Text>
        </Pressable>
      ) : null}
    </View>
  );
}

export const OverviewTab: React.FC<OverviewTabProps> = ({
  student,
  attendancePct,
  feeBalance,
  canViewFees,
  statementLoading,
  statement,
}) => {
  const { palette, typography, spacing, colors, radius, elevation } = useTheme();

  const classLabel = [student.className, student.streamName].filter(Boolean).join(' · ') || 'Unassigned';
  const feeBalanceValue = feeBalance ?? statement?.closing_balance ?? student.outstandingBalance;
  const feesCleared =
    student.feeStatus === 'cleared' || (feeBalanceValue != null && feeBalanceValue <= 0);

  const widgets = useMemo((): StudentSummaryWidgetData[] => {
    const list: StudentSummaryWidgetData[] = [
      {
        id: 'attendance',
        label: 'Attendance',
        value: formatPercent(attendancePct ?? null),
        delta: 'Last 90 days',
        icon: 'checkmark-circle-outline',
      },
      {
        id: 'status',
        label: 'Enrollment',
        value: student.enrollmentStatus
          ? student.enrollmentStatus.charAt(0).toUpperCase() + student.enrollmentStatus.slice(1)
          : '—',
        delta: student.category ?? 'Student',
        icon: 'school-outline',
      },
    ];

    if (canViewFees) {
      list.push({
        id: 'balance',
        label: 'Fee balance',
        value: formatKes(feeBalanceValue),
        delta: student.feeStatus === 'pending' ? 'Outstanding' : 'Cleared',
        icon: 'wallet-outline',
      });
    } else {
      list.push({
        id: 'fees',
        label: 'Fees',
        value: student.feeStatus === 'pending' ? 'Pending' : 'Cleared',
        icon: 'wallet-outline',
      });
    }

    const primary = student.guardians.find((g) => g.isPrimary) ?? student.guardians[0];
    list.push({
      id: 'parent',
      label: 'Primary contact',
      value: primary?.name ?? student.parent?.fatherName ?? student.parent?.motherName ?? '—',
      delta: primary?.phone ?? student.parent?.fatherPhone ?? student.parent?.motherPhone ?? undefined,
      icon: 'people-outline',
    });

    return list;
  }, [student, attendancePct, feeBalanceValue, canViewFees]);

  const timelineEvents = useMemo((): StudentTimelineEventData[] => {
    const events = buildStudentTimeline({
      statement: statement ?? null,
      admissionDate: student.admissionDate,
      updatedAt: null,
    });
    return events.map((e) => ({
      id: e.id,
      title: e.title,
      subtitle: e.subtitle,
      occurredAtLabel: formatDateLabel(e.occurredAt),
      kind: e.kind,
    }));
  }, [statement, student]);

  return (
    <View>
      <View
        style={[
          elevation[1],
          {
            backgroundColor: palette.surfaceRaised,
            borderRadius: radius.card,
            borderWidth: StyleSheet.hairlineWidth,
            borderColor: palette.borderSubtle,
            padding: spacing.md,
            marginBottom: spacing.md,
          },
        ]}
      >
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.md }}>
          <Soft3DIcon name="school-outline" size={52} tone="indigo" />
          <View style={{ flex: 1 }}>
            <Text
              style={{
                color: palette.textMain,
                fontSize: typography.title.fontSize,
                fontWeight: '800',
                letterSpacing: -0.2,
              }}
              numberOfLines={2}
            >
              {student.fullName}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: 2,
              }}
            >
              {classLabel}
            </Text>
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                marginTop: 2,
              }}
            >
              Adm · {student.admissionNumber || '—'}
            </Text>
          </View>
        </View>

        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: spacing.md }}>
          <StatusChip
            label={student.enrollmentStatus === 'active' ? 'Active' : student.enrollmentStatus}
            tone={student.enrollmentStatus === 'active' ? 'success' : 'neutral'}
          />
          {canViewFees ? (
            <StatusChip
              label={feesCleared ? 'Fees cleared' : 'Fees outstanding'}
              tone={feesCleared ? 'success' : 'warning'}
            />
          ) : null}
          {student.gender ? (
            <StatusChip
              label={student.gender.charAt(0).toUpperCase() + student.gender.slice(1)}
              tone="neutral"
            />
          ) : null}
        </View>
      </View>

      <StudentSummaryWidgets widgets={widgets} />

      {statementLoading && canViewFees ? (
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} />
      ) : null}

      <View style={{ marginTop: spacing.lg }}>
        <StaffFieldSection
          title="Quick profile"
          rows={[
            { label: 'Class / stream', value: classLabel },
            { label: 'Category', value: student.category },
            { label: 'Date of birth', value: formatDateLabel(student.dateOfBirth) },
            { label: 'Admission date', value: formatDateLabel(student.admissionDate) },
            { label: 'Enrollment year', value: student.enrollmentYear != null ? String(student.enrollmentYear) : null },
            { label: 'NEMIS', value: student.nemisNumber },
            { label: 'Phone', value: student.phone },
            { label: 'Email', value: student.email },
            { label: 'Address', value: student.address },
          ]}
        />
      </View>

      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.overline.fontSize,
          fontWeight: typography.overline.fontWeight,
          letterSpacing: typography.overline.letterSpacing,
          textTransform: 'uppercase',
          marginBottom: spacing.sm,
        }}
      >
        Family contacts
      </Text>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md }}>
        <ContactMiniCard
          label="Father"
          name={student.parent?.fatherName}
          phone={student.parent?.fatherPhone}
        />
        <ContactMiniCard
          label="Mother"
          name={student.parent?.motherName}
          phone={student.parent?.motherPhone}
        />
        <ContactMiniCard
          label="Guardian"
          name={student.parent?.guardianName}
          phone={student.parent?.guardianPhone}
        />
        <ContactMiniCard
          label="Emergency"
          name={student.emergencyContact?.name}
          phone={student.emergencyContact?.phone}
        />
      </View>

      <StudentTimeline events={timelineEvents} />
    </View>
  );
};
