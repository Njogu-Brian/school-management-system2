import type { StudentDetail } from '@erp/core';
import { useMedicalRecords } from '@erp/core';
import { FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface HealthTabProps {
  student: StudentDetail;
}

export const HealthTab: React.FC<HealthTabProps> = ({ student }) => {
  const { colors, palette, fontSizes } = useTheme();
  const medicalQuery = useMedicalRecords(student.id);

  const profileRows = useMemo(
    () => [
      { label: 'Blood group', value: student.bloodGroup ?? '—' },
      { label: 'Preferred hospital', value: student.preferredHospital ?? '—' },
      {
        label: 'Allergies',
        value: student.hasAllergies
          ? student.allergiesNotes?.trim() || 'Yes (no notes)'
          : 'None reported',
      },
      {
        label: 'Immunization',
        value:
          student.isFullyImmunized == null
            ? '—'
            : student.isFullyImmunized
              ? 'Fully immunized'
              : 'Not fully immunized',
      },
      { label: 'Emergency contact', value: student.emergencyContact.name ?? '—' },
      { label: 'Emergency phone', value: student.emergencyContact.phone ?? '—' },
    ],
    [student],
  );

  const clinicRows = useMemo(
    () =>
      (medicalQuery.data ?? []).map((record) => ({
        label: record.title ?? record.record_type ?? 'Medical record',
        value: [record.record_date, record.doctor_name, record.vaccination_name]
          .filter(Boolean)
          .join(' · ') || '—',
      })),
    [medicalQuery.data],
  );

  return (
    <>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 8 }}>
        Profile: GET /students/{'{id}'} · Clinic: GET /students/{'{id}'}/medical-records
      </Text>
      <FinanceFieldSection title="Health profile" rows={profileRows} />
      {medicalQuery.isLoading ? (
        <View style={{ paddingVertical: 16, alignItems: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      ) : clinicRows.length > 0 ? (
        <FinanceFieldSection title="Clinic records" rows={clinicRows} />
      ) : (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 12 }}>
          No clinic visit records on file.
        </Text>
      )}
    </>
  );
};
