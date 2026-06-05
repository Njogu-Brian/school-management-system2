import {
  useAdmissionActions,
  useClassroomStreams,
  type ApplicationDetail,
  type EnrolledStudentRecord,
} from '@erp/core';
import { ApplicationFieldSection, Button, TextField } from '@erp/ui';
import React, { useEffect, useMemo, useState } from 'react';
import { Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface EnrollmentTabProps {
  application: ApplicationDetail;
  onViewStudent?: (student: EnrolledStudentRecord) => void;
}

const ChipRow: React.FC<{
  label: string;
  options: Array<{ id: number | string; label: string }>;
  value: number | string | null;
  onChange: (id: number | string) => void;
}> = ({ label, options, value, onChange }) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginBottom: spacing.xs }}>
        {label}
      </Text>
      <View style={[styles.chipRow, { gap: spacing.xs }]}>
        {options.map((opt) => {
          const active = value === opt.id;
          return (
            <Pressable
              key={String(opt.id)}
              onPress={() => onChange(opt.id)}
              style={[
                styles.chip,
                {
                  borderRadius: radius.full,
                  backgroundColor: active ? `${colors.primary}18` : palette.surface,
                  borderColor: active ? colors.primary : palette.border,
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.xs,
                },
              ]}
            >
              <Text
                style={{
                  color: active ? colors.primary : palette.textSecondary,
                  fontSize: fontSizes.xs,
                  fontWeight: '700',
                }}
              >
                {opt.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
};

export const EnrollmentTab: React.FC<EnrollmentTabProps> = ({ application, onViewStudent }) => {
  const { palette, colors, spacing, fontSizes } = useTheme();
  const enrollment = application.enrollment;
  const { enroll } = useAdmissionActions(application.id);

  const defaultClassroomId =
    enrollment.classroom_id ??
    enrollment.preferred_classroom_id ??
    enrollment.classrooms[0]?.id ??
    null;
  const defaultCategoryId = enrollment.student_categories[0]?.id ?? null;
  const defaultTerm = enrollment.enrollment_term_options[0] ?? null;

  const [classroomId, setClassroomId] = useState<number | null>(defaultClassroomId);
  const [streamId, setStreamId] = useState<number | null>(enrollment.stream_id);
  const [categoryId, setCategoryId] = useState<number | null>(defaultCategoryId);
  const [termKey, setTermKey] = useState(
    defaultTerm ? `${defaultTerm.year}-${defaultTerm.term}` : '',
  );
  const [tripId, setTripId] = useState<number | null>(enrollment.trip_id);
  const [dropOffId, setDropOffId] = useState<number | null>(enrollment.drop_off_point_id);
  const [residentialArea, setResidentialArea] = useState(application.residentialArea ?? '');

  const streamsQuery = useClassroomStreams(classroomId, { enabled: enrollment.can_enroll });
  const streams = streamsQuery.data ?? [];
  const streamsRequired = streams.length > 0;

  useEffect(() => {
    if (!streamsRequired) {
      setStreamId(null);
      return;
    }
    if (streamId != null && streams.some((s) => s.id === streamId)) {
      return;
    }
    setStreamId(streams[0]?.id ?? null);
  }, [streams, streamsRequired, streamId]);

  const selectedTerm = useMemo(() => {
    const [year, term] = termKey.split('-').map(Number);
    if (!year || !term) return null;
    return { year, term };
  }, [termKey]);

  const canSubmit =
    enrollment.can_enroll &&
    classroomId != null &&
    categoryId != null &&
    residentialArea.trim().length > 0 &&
    (!streamsRequired || streamId != null);

  const handleEnroll = () => {
    if (!canSubmit || classroomId == null || categoryId == null) return;

    Alert.alert(
      'Enroll student',
      `Create a student record for ${application.fullName}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Enroll',
          onPress: () => {
            void enroll.mutateAsync({
              classroom_id: classroomId,
              stream_id: streamId,
              category_id: categoryId,
              trip_id: enrollment.transport_needed ? tripId : null,
              drop_off_point_id: enrollment.transport_needed ? dropOffId : null,
              drop_off_point_other: application.dropOffPointOther,
              residential_area: residentialArea.trim(),
              preferred_hospital: application.preferredHospital,
              enrollment_year: selectedTerm?.year,
              enrollment_term: selectedTerm?.term,
              has_allergies: application.hasAllergies,
              allergies_notes: application.allergiesNotes,
              is_fully_immunized: application.isFullyImmunized,
              emergency_contact_name: application.emergencyContactName,
              emergency_contact_phone: application.emergencyContactPhone,
              marital_status: application.maritalStatus,
            }).then((result) => {
              Alert.alert('Enrolled', `${result.student.full_name} was enrolled successfully.`, [
                { text: 'Stay here', style: 'cancel' },
                {
                  text: 'View student',
                  onPress: () => onViewStudent?.(result.student),
                },
              ]);
            }).catch((err: Error) => {
              Alert.alert('Enrollment failed', err.message);
            });
          },
        },
      ],
    );
  };

  if (!enrollment.can_enroll) {
    return (
      <ScrollView showsVerticalScrollIndicator={false}>
        <ApplicationFieldSection
          title="Enrollment status"
          rows={[
            { label: 'Status', value: enrollment.application_status },
            { label: 'Enrolled', value: enrollment.enrolled ? 'Yes' : 'No' },
          ]}
        />
        <View style={{ paddingBottom: spacing.xl }}>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
            {enrollment.enrolled
              ? 'This application has already been enrolled.'
              : 'This application cannot be enrolled (rejected or closed).'}
          </Text>
        </View>
      </ScrollView>
    );
  }

  return (
    <ScrollView showsVerticalScrollIndicator={false}>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '600', marginBottom: spacing.sm }}>
        Complete enrollment
      </Text>

      <ChipRow
        label="Class *"
        options={enrollment.classrooms.map((c) => ({ id: c.id, label: c.name }))}
        value={classroomId}
        onChange={(id) => setClassroomId(Number(id))}
      />

      {streamsRequired ? (
        <ChipRow
          label="Stream *"
          options={streams.map((s) => ({ id: s.id, label: s.name }))}
          value={streamId}
          onChange={(id) => setStreamId(Number(id))}
        />
      ) : null}

      <ChipRow
        label="Student category *"
        options={enrollment.student_categories.map((c) => ({ id: c.id, label: c.name }))}
        value={categoryId}
        onChange={(id) => setCategoryId(Number(id))}
      />

      <ChipRow
        label="Enrollment term"
        options={enrollment.enrollment_term_options.map((opt) => ({
          id: `${opt.year}-${opt.term}`,
          label: opt.label,
        }))}
        value={termKey}
        onChange={(id) => setTermKey(String(id))}
      />

      {enrollment.transport_needed ? (
        <>
          <ChipRow
            label="Trip"
            options={[
              { id: 0, label: 'None' },
              ...enrollment.trips.map((t) => ({ id: t.id, label: t.name })),
            ]}
            value={tripId ?? 0}
            onChange={(id) => setTripId(Number(id) === 0 ? null : Number(id))}
          />
          <ChipRow
            label="Drop-off point"
            options={[
              { id: 0, label: 'None' },
              ...enrollment.drop_off_points.map((p) => ({ id: p.id, label: p.name })),
            ]}
            value={dropOffId ?? 0}
            onChange={(id) => setDropOffId(Number(id) === 0 ? null : Number(id))}
          />
        </>
      ) : null}

      <TextField
        label="Residential area *"
        value={residentialArea}
        onChangeText={setResidentialArea}
        placeholder="Enter residential area"
      />

      {enroll.isError ? (
        <Text style={{ color: colors.error, fontSize: fontSizes.sm, marginBottom: spacing.sm }}>
          {(enroll.error as Error).message}
        </Text>
      ) : null}

      <Button
        label="Enroll student"
        onPress={handleEnroll}
        loading={enroll.isPending}
        disabled={!canSubmit}
        style={{ marginBottom: spacing.xl }}
      />
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  chipRow: { flexDirection: 'row', flexWrap: 'wrap' },
  chip: { borderWidth: StyleSheet.hairlineWidth },
});
