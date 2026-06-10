import { useCan, useCreateMedicalRecord } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<StudentsStackParamList, 'MedicalRecordForm'>;

const RECORD_TYPES = [
  { value: 'checkup', label: 'Checkup' },
  { value: 'medication', label: 'Medication' },
  { value: 'vaccination', label: 'Vaccination' },
  { value: 'incident', label: 'Incident' },
  { value: 'certificate', label: 'Certificate' },
  { value: 'other', label: 'Other' },
] as const;

type RecordType = (typeof RECORD_TYPES)[number]['value'];

const today = () => new Date().toISOString().slice(0, 10);

export const MedicalRecordFormScreen: React.FC<Props> = ({ navigation, route }) => {
  const { studentId, studentName } = route.params;
  const canView = useCan('students.view');
  const { palette, spacing, typography } = useTheme();
  const createMutation = useCreateMedicalRecord();

  const [recordType, setRecordType] = useState<RecordType>('checkup');
  const [recordDate, setRecordDate] = useState(today());
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [doctorName, setDoctorName] = useState('');
  const [clinicHospital, setClinicHospital] = useState('');
  const [medicationName, setMedicationName] = useState('');
  const [medicationDosage, setMedicationDosage] = useState('');
  const [vaccinationName, setVaccinationName] = useState('');
  const [nextDueDate, setNextDueDate] = useState('');
  const [notes, setNotes] = useState('');

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const canSubmit = title.trim().length > 0 && recordDate.trim().length > 0;

  const onSave = async () => {
    if (!canSubmit) {
      showError('Validation', 'Title and record date are required.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        studentId,
        record_type: recordType,
        record_date: recordDate.trim(),
        title: title.trim(),
        description: description.trim() || undefined,
        doctor_name: doctorName.trim() || undefined,
        clinic_hospital: clinicHospital.trim() || undefined,
        medication_name: medicationName.trim() || undefined,
        medication_dosage: medicationDosage.trim() || undefined,
        vaccination_name: vaccinationName.trim() || undefined,
        next_due_date: nextDueDate.trim() || undefined,
        notes: notes.trim() || undefined,
      });
      showSuccess('Saved', 'Medical record added.', () => navigation.goBack());
    } catch (err) {
      showError('Save failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Log medical record"
        subtitle={studentName ?? `Student #${studentId}`}
        onBack={() => navigation.goBack()}
      />

      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
          letterSpacing: 0.4,
          marginBottom: spacing.xs,
        }}
      >
        RECORD TYPE
      </Text>
      <FilterChipRow>
        {RECORD_TYPES.map((t) => (
          <FilterChip key={t.value} label={t.label} active={recordType === t.value} onPress={() => setRecordType(t.value)} />
        ))}
      </FilterChipRow>

      <TextField label="Title" value={title} onChangeText={setTitle} placeholder="e.g. Clinic visit — fever" />
      <TextField label="Date (YYYY-MM-DD)" value={recordDate} onChangeText={setRecordDate} placeholder={today()} />
      <TextField
        label="Description (optional)"
        value={description}
        onChangeText={setDescription}
        placeholder="Symptoms, diagnosis, treatment given…"
        multiline
        numberOfLines={4}
        textAlignVertical="top"
      />
      <TextField label="Doctor / nurse (optional)" value={doctorName} onChangeText={setDoctorName} placeholder="Attending clinician" />
      <TextField
        label="Clinic / hospital (optional)"
        value={clinicHospital}
        onChangeText={setClinicHospital}
        placeholder="Facility name"
      />

      {recordType === 'medication' ? (
        <>
          <TextField label="Medication name" value={medicationName} onChangeText={setMedicationName} placeholder="e.g. Paracetamol" />
          <TextField label="Dosage" value={medicationDosage} onChangeText={setMedicationDosage} placeholder="e.g. 250mg twice daily" />
        </>
      ) : null}

      {recordType === 'vaccination' ? (
        <>
          <TextField label="Vaccine name" value={vaccinationName} onChangeText={setVaccinationName} placeholder="e.g. Tetanus booster" />
          <TextField label="Next due date (optional)" value={nextDueDate} onChangeText={setNextDueDate} placeholder="YYYY-MM-DD" />
        </>
      ) : null}

      <TextField label="Notes (optional)" value={notes} onChangeText={setNotes} placeholder="Anything else worth recording" />

      <Button
        label={createMutation.isPending ? 'Saving…' : 'Save record'}
        onPress={() => void onSave()}
        disabled={!canSubmit || createMutation.isPending}
        loading={createMutation.isPending}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
