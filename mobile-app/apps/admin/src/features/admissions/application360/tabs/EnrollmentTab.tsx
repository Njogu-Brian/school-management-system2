import {
  useAdmissionActions,
  useClassroomStreams,
  type ApplicationDetail,
  type EnrolledStudentRecord,
} from '@erp/core';
import { ApplicationFieldSection, Button, TextField } from '@erp/ui';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { confirmAction, showError } from '../../../shared/utils/feedback';

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
  const { palette, colors, spacing, typography, radius } = useTheme();

  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.body.fontSize,
          marginBottom: spacing.xs,
        }}
      >
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
                  backgroundColor: active ? `${colors.primary}18` : palette.surfaceRaised,
                  borderColor: active ? colors.primary : palette.borderSubtle,
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.xs,
                },
              ]}
            >
              <Text
                style={{
                  color: active ? colors.primary : palette.textSecondary,
                  fontSize: typography.overline.fontSize,
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

function todayIso(): string {
  return new Date().toISOString().slice(0, 10);
}

export const EnrollmentTab: React.FC<EnrollmentTabProps> = ({ application, onViewStudent }) => {
  const { palette, colors, spacing, typography } = useTheme();
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
  const [dropOffId, setDropOffId] = useState<number | null | 'other'>(
    enrollment.drop_off_point_id,
  );
  const [dropOffOther, setDropOffOther] = useState(application.dropOffPointOther ?? '');
  const [transportFee, setTransportFee] = useState('');
  const [admissionDate, setAdmissionDate] = useState(todayIso());
  const [residentialArea, setResidentialArea] = useState(application.residentialArea ?? '');
  const [preferredHospital, setPreferredHospital] = useState(application.preferredHospital ?? '');
  const [emergencyName, setEmergencyName] = useState(application.emergencyContactName ?? '');
  const [emergencyPhone, setEmergencyPhone] = useState(application.emergencyContactPhone ?? '');
  const [allergiesNotes, setAllergiesNotes] = useState(application.allergiesNotes ?? '');
  const [hasAllergies, setHasAllergies] = useState(!!application.hasAllergies);
  const [isFullyImmunized, setIsFullyImmunized] = useState(!!application.isFullyImmunized);

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
    (!streamsRequired || streamId != null) &&
    (dropOffId !== 'other' || dropOffOther.trim().length > 0);

  const handleEnroll = () => {
    if (!canSubmit || classroomId == null || categoryId == null) return;

    const feeParsed = transportFee.trim() ? Number(transportFee.trim()) : null;
    if (feeParsed != null && (Number.isNaN(feeParsed) || feeParsed < 0)) {
      showError('Invalid fee', 'Enter a valid transport fee amount.');
      return;
    }

    confirmAction(
      'Enroll student',
      `Create a student record for ${application.fullName}? Class will be assigned and fees invoiced.`,
      'Enroll',
      () => {
        void enroll
          .mutateAsync({
            classroom_id: classroomId,
            stream_id: streamId,
            category_id: categoryId,
            trip_id: tripId,
            drop_off_point_id: dropOffId === 'other' ? null : dropOffId,
            drop_off_point_other: dropOffId === 'other' ? dropOffOther.trim() : null,
            transport_fee_amount: feeParsed,
            admission_date: admissionDate || undefined,
            residential_area: residentialArea.trim(),
            preferred_hospital: preferredHospital.trim() || null,
            enrollment_year: selectedTerm?.year,
            enrollment_term: selectedTerm?.term,
            has_allergies: hasAllergies,
            allergies_notes: allergiesNotes.trim() || null,
            is_fully_immunized: isFullyImmunized,
            emergency_contact_name: emergencyName.trim() || null,
            emergency_contact_phone: emergencyPhone.trim() || null,
            marital_status: application.maritalStatus,
          })
          .then((result) => {
            confirmAction(
              'Enrolled',
              `${result.student.full_name} was enrolled. Class assigned and fees posted where applicable.`,
              'View student',
              () => onViewStudent?.(result.student),
            );
          })
          .catch((err: Error) => {
            showError('Enrollment failed', err.message);
          });
      },
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
          <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
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
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
          fontWeight: typography.titleSmall.fontWeight,
          marginBottom: spacing.sm,
        }}
      >
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

      <TextField
        label="Admission date"
        value={admissionDate}
        onChangeText={setAdmissionDate}
        placeholder="YYYY-MM-DD"
      />

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
          { id: 'other', label: 'Other' },
        ]}
        value={dropOffId ?? 0}
        onChange={(id) => {
          if (id === 'other') {
            setDropOffId('other');
            return;
          }
          setDropOffId(Number(id) === 0 ? null : Number(id));
        }}
      />
      {dropOffId === 'other' ? (
        <TextField
          label="Other drop-off point *"
          value={dropOffOther}
          onChangeText={setDropOffOther}
          placeholder="Describe drop-off location"
        />
      ) : null}
      <TextField
        label="Transport fee amount"
        value={transportFee}
        onChangeText={setTransportFee}
        placeholder="Optional — included on first invoice"
        keyboardType="decimal-pad"
      />

      <TextField
        label="Residential area *"
        value={residentialArea}
        onChangeText={setResidentialArea}
        placeholder="Enter residential area"
      />

      <ChipRow
        label="Has allergies"
        options={[
          { id: 'yes', label: 'Yes' },
          { id: 'no', label: 'No' },
        ]}
        value={hasAllergies ? 'yes' : 'no'}
        onChange={(id) => setHasAllergies(id === 'yes')}
      />
      {hasAllergies ? (
        <TextField
          label="Allergy notes"
          value={allergiesNotes}
          onChangeText={setAllergiesNotes}
          multiline
        />
      ) : null}
      <ChipRow
        label="Fully immunized"
        options={[
          { id: 'yes', label: 'Yes' },
          { id: 'no', label: 'No' },
        ]}
        value={isFullyImmunized ? 'yes' : 'no'}
        onChange={(id) => setIsFullyImmunized(id === 'yes')}
      />
      <TextField
        label="Preferred hospital"
        value={preferredHospital}
        onChangeText={setPreferredHospital}
      />
      <TextField
        label="Emergency contact name"
        value={emergencyName}
        onChangeText={setEmergencyName}
      />
      <TextField
        label="Emergency contact phone"
        value={emergencyPhone}
        onChangeText={setEmergencyPhone}
        keyboardType="phone-pad"
      />

      {enroll.isError ? (
        <Text
          style={{
            color: colors.error,
            fontSize: typography.body.fontSize,
            marginBottom: spacing.sm,
          }}
        >
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
