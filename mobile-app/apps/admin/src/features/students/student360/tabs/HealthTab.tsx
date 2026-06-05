import type { StudentDetail } from '@erp/core';
import { FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { Text } from 'react-native';
import { useTheme } from '@erp/ui';

export interface HealthTabProps {
  student: StudentDetail;
}

/** Health fields from `GET /students/{id}` — no dedicated medical records API. */
export const HealthTab: React.FC<HealthTabProps> = ({ student }) => {
  const { palette, fontSizes } = useTheme();

  const rows = useMemo(
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

  return (
    <>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 8 }}>
        Source: GET /students/{'{id}'} (health fields on student profile)
      </Text>
      <FinanceFieldSection title="Health profile" rows={rows} />
    </>
  );
};
