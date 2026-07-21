import { buildStudentTimeline, type StudentDetail, type StudentStatementRecord } from '@erp/core';
import {
  StudentSummaryWidgets,
  StudentTimeline,
  type StudentSummaryWidgetData,
  type StudentTimelineEventData,
} from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
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

export const OverviewTab: React.FC<OverviewTabProps> = ({
  student,
  attendancePct,
  feeBalance,
  canViewFees,
  statementLoading,
  statement,
}) => {
  const { palette, typography, spacing, colors } = useTheme();

  const widgets = useMemo((): StudentSummaryWidgetData[] => {
    const list: StudentSummaryWidgetData[] = [
      {
        id: 'attendance',
        label: 'Attendance',
        value: formatPercent(attendancePct ?? null),
        delta: 'Last 90 days (server)',
        icon: 'checkmark-circle-outline',
      },
    ];

    if (canViewFees) {
      list.push({
        id: 'balance',
        label: 'Fee balance',
        value: formatKes(feeBalance ?? statement?.closing_balance ?? student.outstandingBalance),
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
      delta: primary?.phone ?? undefined,
      icon: 'people-outline',
    });

    return list;
  }, [student, attendancePct, feeBalance, statement, canViewFees]);

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
      <StudentSummaryWidgets widgets={widgets} />

      {statementLoading && canViewFees ? (
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} />
      ) : null}

      <View style={{ marginTop: spacing.lg }}>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            fontWeight: '700',
            textTransform: 'uppercase',
            letterSpacing: 0.4,
            marginBottom: spacing.sm,
          }}
        >
          Parent summary
        </Text>
        <Text style={{ color: palette.textPrimary, fontSize: typography.body.fontSize }}>
          {student.parent?.fatherName ? `Father: ${student.parent.fatherName}` : 'Father: —'}
        </Text>
        <Text style={{ color: palette.textPrimary, fontSize: typography.body.fontSize, marginTop: 4 }}>
          {student.parent?.motherName ? `Mother: ${student.parent.motherName}` : 'Mother: —'}
        </Text>
        {(student.parent?.fatherPhone || student.parent?.motherPhone) && (
          <View style={{ marginTop: 4 }}>
            {[student.parent?.fatherPhone, student.parent?.motherPhone]
              .filter(Boolean)
              .map((phone) => (
                <Pressable key={phone} onPress={() => void openPhoneActions(phone!)}>
                  <Text
                    style={{
                      color: colors.primary,
                      fontSize: typography.caption.fontSize,
                      textDecorationLine: 'underline',
                      marginTop: 2,
                    }}
                  >
                    {phone}
                  </Text>
                </Pressable>
              ))}
          </View>
        )}
      </View>

      <StudentTimeline events={timelineEvents} />
    </View>
  );
};
