import { type StudentDetail, type StudentTransportLeg } from '@erp/core';
import { EmptyState, FinanceFieldSection, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { Image, Text } from 'react-native';

export interface TransportTabProps {
  student: StudentDetail;
}

function hasTransportAssignment(student: StudentDetail): boolean {
  return Boolean(
    student.transportMorning?.tripName ||
      student.transportEvening?.tripName ||
      student.tripName ||
      student.dropOffPointName ||
      student.dropOffPointOther ||
      (student.transportSummary && student.transportSummary !== 'No transport assigned'),
  );
}

function legRows(label: string, leg: StudentTransportLeg | null, fallbackName?: string | null) {
  if (!leg && !fallbackName) return [];
  return [
    { label: `${label} trip`, value: leg?.tripName ?? fallbackName ?? '—' },
    { label: `${label} vehicle`, value: leg?.vehicle ?? '—' },
    { label: `${label} drop-off`, value: leg?.dropOffPoint ?? '—' },
    { label: `${label} driver`, value: leg?.driverName ?? '—' },
  ];
}

/** Transport assignment using trip/drop-off names from student detail. */
export const TransportTab: React.FC<TransportTabProps> = ({ student }) => {
  const { palette, spacing, radius } = useTheme();

  const morning = student.transportMorning;
  const evening = student.transportEvening;
  const photoUrl = morning?.vehiclePhotoUrl || evening?.vehiclePhotoUrl;

  const assignmentRows = useMemo(() => {
    const rows = [
      ...legRows('Morning', morning),
      ...legRows('Evening', evening, student.tripName),
    ];
    if (student.dropOffPointOther) {
      rows.push({ label: 'Notes', value: student.dropOffPointOther });
    }
    if (rows.length === 0 && student.transportSummary) {
      rows.push({ label: 'Assignment', value: student.transportSummary });
    }
    return rows;
  }, [morning, evening, student.tripName, student.dropOffPointOther, student.transportSummary]);

  if (!hasTransportAssignment(student)) {
    return (
      <EmptyState
        title="No transport assignment"
        message="This student is not linked to a school transport trip."
        icon="bus-outline"
      />
    );
  }

  return (
    <>
      {photoUrl ? (
        <Image
          source={{ uri: photoUrl }}
          style={{
            width: '100%',
            height: 140,
            borderRadius: radius.lg,
            marginBottom: spacing.md,
            backgroundColor: palette.surface,
          }}
        />
      ) : null}
      {student.transportSummary ? (
        <Text style={{ color: palette.textMain, fontWeight: '700', marginBottom: spacing.sm }}>
          {student.transportSummary}
        </Text>
      ) : null}
      {assignmentRows.length > 0 ? <FinanceFieldSection title="Assignment" rows={assignmentRows} /> : null}
    </>
  );
};
