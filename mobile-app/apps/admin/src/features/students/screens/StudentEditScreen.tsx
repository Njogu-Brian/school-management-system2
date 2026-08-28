import {
  emptyKemisLearnerValues,
  emptyKemisParentSlotValues,
  formatParentSlotName,
  kemisLearnerFromApi,
  kemisLearnerPayload,
  kemisParentSlotFromApi,
  kemisParentSlotPayload,
  studentsApi,
  useClassroomStreams,
  useClassrooms,
  useKemisOptions,
  useStudentCategories,
  useUpdateStudent,
  type KemisLearnerValues,
  type KemisParentSlotValues,
} from '@erp/core';
import type { StreamRecord } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  KemisLearnerFields,
  KemisParentIdentityFields,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, Switch, Text, View } from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<StudentsStackParamList, 'StudentEdit'>;

type MuteParent = '' | 'father' | 'mother';

export const StudentEditScreen: React.FC<Props> = ({ route, navigation }) => {
  const { studentId } = route.params;
  const { colors, spacing, typography, palette } = useTheme();
  const updateMutation = useUpdateStudent(studentId);
  const classroomsQuery = useClassrooms();
  const categoriesQuery = useStudentCategories();
  const kemisOptionsQuery = useKemisOptions();

  const [loading, setLoading] = useState(true);
  const [firstName, setFirstName] = useState('');
  const [middleName, setMiddleName] = useState('');
  const [lastName, setLastName] = useState('');
  const [gender, setGender] = useState('male');
  const [dob, setDob] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [residentialArea, setResidentialArea] = useState('');
  const [kemisLearner, setKemisLearner] = useState<KemisLearnerValues>(emptyKemisLearnerValues());
  const [nemisNumber, setNemisNumber] = useState('');
  const [knecNumber, setKnecNumber] = useState('');
  const [kcpeKjseaYear, setKcpeKjseaYear] = useState('');
  const [preferredHospital, setPreferredHospital] = useState('');
  const [hasAllergies, setHasAllergies] = useState(false);
  const [allergiesNotes, setAllergiesNotes] = useState('');
  const [isFullyImmunized, setIsFullyImmunized] = useState<boolean | null>(null);
  const [emergencyName, setEmergencyName] = useState('');
  const [emergencyPhone, setEmergencyPhone] = useState('');
  const [admissionDate, setAdmissionDate] = useState('');
  const [father, setFather] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [mother, setMother] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [guardian, setGuardian] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [guardianRelationship, setGuardianRelationship] = useState('');
  const [maritalStatus, setMaritalStatus] = useState('');
  const [muteParent, setMuteParent] = useState<MuteParent>('');

  const streamsQuery = useClassroomStreams(classroomId, { enabled: (classroomId ?? 0) > 0 });
  const kemisOptions = kemisOptionsQuery.data;

  useEffect(() => {
    void studentsApi.getById(studentId).then((res) => {
      const s = res.data;
      if (!s) return;
      setFirstName(s.first_name);
      setMiddleName(s.middle_name ?? '');
      setLastName(s.last_name);
      setGender(s.gender ?? 'male');
      setDob((s.date_of_birth ?? '').slice(0, 10));
      setClassroomId(s.classroom_id ?? s.class_id ?? null);
      setStreamId(s.stream_id ?? null);
      setCategoryId(s.category_id ?? null);
      setResidentialArea(s.residential_area ?? s.address ?? '');
      setKemisLearner(kemisLearnerFromApi(s as Record<string, unknown>));
      setNemisNumber(s.nemis_number ?? '');
      setKnecNumber(s.knec_assessment_number ?? '');
      setKcpeKjseaYear(s.kcpe_kjsea_year != null ? String(s.kcpe_kjsea_year) : '');
      setPreferredHospital(s.preferred_hospital ?? '');
      setHasAllergies(Boolean(s.has_allergies));
      setAllergiesNotes(s.allergies_notes ?? '');
      setIsFullyImmunized(s.is_fully_immunized ?? null);
      setEmergencyName(s.emergency_contact_name ?? '');
      setEmergencyPhone(s.emergency_contact_phone ?? '');
      setAdmissionDate((s.admission_date ?? s.created_at ?? '').slice(0, 10));
      const p = s.parent as Record<string, unknown> | null | undefined;
      setFather(kemisParentSlotFromApi(p, 'father'));
      setMother(kemisParentSlotFromApi(p, 'mother'));
      setGuardian(kemisParentSlotFromApi(p, 'guardian'));
      setGuardianRelationship(String(p?.guardian_relationship ?? ''));
      setMaritalStatus(String(p?.marital_status ?? ''));
      const mute = p?.school_notifications_muted_parent;
      setMuteParent(mute === 'father' || mute === 'mother' ? mute : '');
      setLoading(false);
    });
  }, [studentId]);

  const save = async () => {
    if (!classroomId || !categoryId) {
      showError('Missing fields', 'Class and category are required.');
      return;
    }
    const parentPhone = father.phone.trim() || mother.phone.trim() || guardian.phone.trim();
    const parentName =
      formatParentSlotName(father) || formatParentSlotName(mother) || formatParentSlotName(guardian);
    if (!parentName || !parentPhone) {
      showError('Parent required', 'At least one parent/guardian name and phone is required.');
      return;
    }
    try {
      await updateMutation.mutateAsync({
        first_name: firstName.trim(),
        middle_name: middleName.trim() || null,
        last_name: lastName.trim(),
        gender,
        dob: dob || null,
        classroom_id: classroomId,
        stream_id: streamId,
        category_id: categoryId,
        residential_area: residentialArea.trim(),
        nemis_number: nemisNumber.trim() || null,
        knec_assessment_number: knecNumber.trim() || null,
        kcpe_kjsea_year: kcpeKjseaYear.trim() ? Number(kcpeKjseaYear.trim()) : null,
        preferred_hospital: preferredHospital.trim() || null,
        has_allergies: hasAllergies,
        allergies_notes: allergiesNotes.trim() || null,
        is_fully_immunized: isFullyImmunized,
        emergency_contact_name: emergencyName.trim() || null,
        emergency_contact_phone: emergencyPhone.trim() || null,
        admission_date: admissionDate,
        guardian_relationship: guardianRelationship.trim() || null,
        marital_status: maritalStatus || null,
        school_notifications_muted_parent: muteParent || null,
        ...kemisLearnerPayload(kemisLearner),
        ...kemisParentSlotPayload('father', father),
        ...kemisParentSlotPayload('mother', mother),
        ...kemisParentSlotPayload('guardian', guardian),
      });
      showSuccess('Saved', 'Student profile updated.');
      navigation.goBack();
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Update failed.');
    }
  };

  if (loading || kemisOptionsQuery.isLoading || !kemisOptions) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Edit student" onBack={() => navigation.goBack()} />

        <SectionTitle label="Student" palette={palette} typography={typography} spacing={spacing} />
        <TextField label="First name" value={firstName} onChangeText={setFirstName} />
        <TextField label="Middle name" value={middleName} onChangeText={setMiddleName} />
        <TextField label="Last name" value={lastName} onChangeText={setLastName} />
        <FilterChipRow label="Gender">
          {(['male', 'female', 'other'] as const).map((g) => (
            <FilterChip key={g} label={g} active={gender === g} onPress={() => setGender(g)} />
          ))}
        </FilterChipRow>
        <TextField label="Date of birth (YYYY-MM-DD)" value={dob} onChangeText={setDob} />
        <FilterChipRow label="Class">
          {(classroomsQuery.data ?? []).map((c) => (
            <FilterChip key={c.id} label={c.name} active={classroomId === c.id} onPress={() => { setClassroomId(c.id); setStreamId(null); }} />
          ))}
        </FilterChipRow>
        {(streamsQuery.data ?? []).length > 0 ? (
          <FilterChipRow label="Stream">
            {(streamsQuery.data ?? []).map((s: StreamRecord) => (
              <FilterChip key={s.id} label={s.name} active={streamId === s.id} onPress={() => setStreamId(s.id)} />
            ))}
          </FilterChipRow>
        ) : null}
        <FilterChipRow label="Category">
          {(categoriesQuery.data ?? []).map((c) => (
            <FilterChip key={c.id} label={c.name} active={categoryId === c.id} onPress={() => setCategoryId(c.id)} />
          ))}
        </FilterChipRow>
        <TextField label="Residential area" value={residentialArea} onChangeText={setResidentialArea} />
        <TextField label="NEMIS number" value={nemisNumber} onChangeText={setNemisNumber} />
        <TextField label="KNEC assessment / KCPE index" value={knecNumber} onChangeText={setKnecNumber} />
        <TextField label="KCPE / KJSEA year" value={kcpeKjseaYear} onChangeText={setKcpeKjseaYear} keyboardType="number-pad" />
        <TextField label="Admission date (YYYY-MM-DD)" value={admissionDate} onChangeText={setAdmissionDate} />

        <SectionTitle label="Birth & registration (KEMIS)" palette={palette} typography={typography} spacing={spacing} />
        <KemisLearnerFields values={kemisLearner} onChange={setKemisLearner} options={kemisOptions} />

        <SectionTitle label="Health" palette={palette} typography={typography} spacing={spacing} />
        <TextField label="Preferred hospital" value={preferredHospital} onChangeText={setPreferredHospital} />
        <View style={{ flexDirection: 'row', alignItems: 'center', marginVertical: spacing.sm }}>
          <Switch value={hasAllergies} onValueChange={setHasAllergies} />
          <Text style={{ marginLeft: spacing.sm, color: palette.textPrimary }}>Has allergies</Text>
        </View>
        {hasAllergies ? (
          <TextField label="Allergy notes" value={allergiesNotes} onChangeText={setAllergiesNotes} multiline />
        ) : null}
        <FilterChipRow label="Fully immunized">
          <FilterChip label="Yes" active={isFullyImmunized === true} onPress={() => setIsFullyImmunized(true)} />
          <FilterChip label="No" active={isFullyImmunized === false} onPress={() => setIsFullyImmunized(false)} />
          <FilterChip label="Unknown" active={isFullyImmunized == null} onPress={() => setIsFullyImmunized(null)} />
        </FilterChipRow>
        <TextField label="Emergency contact name" value={emergencyName} onChangeText={setEmergencyName} />
        <TextField label="Emergency phone" value={emergencyPhone} onChangeText={setEmergencyPhone} keyboardType="phone-pad" />

        <SectionTitle label="Parents" palette={palette} typography={typography} spacing={spacing} />
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
          Complete all father details or all mother details.
        </Text>
        <KemisParentIdentityFields slot="father" title="Father" values={father} onChange={setFather} options={kemisOptions} />
        <KemisParentIdentityFields slot="mother" title="Mother" values={mother} onChange={setMother} options={kemisOptions} />
        <KemisParentIdentityFields
          slot="guardian"
          title="Guardian (optional)"
          values={guardian}
          onChange={setGuardian}
          options={kemisOptions}
          showRelationship
          relationship={guardianRelationship}
          onRelationshipChange={setGuardianRelationship}
        />
        <FilterChipRow label="Marital status">
          {(['married', 'single_parent', 'co_parenting'] as const).map((m) => (
            <FilterChip key={m} label={m.replace('_', ' ')} active={maritalStatus === m} onPress={() => setMaritalStatus(m)} />
          ))}
        </FilterChipRow>
        <FilterChipRow label="School notifications">
          <FilterChip label="Both parents" active={muteParent === ''} onPress={() => setMuteParent('')} />
          <FilterChip label="Mother only" active={muteParent === 'father'} onPress={() => setMuteParent('father')} />
          <FilterChip label="Father only" active={muteParent === 'mother'} onPress={() => setMuteParent('mother')} />
        </FilterChipRow>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
          &quot;Do not contact&quot; — choose which parent should not receive school SMS/email.
        </Text>

        <Button label="Save changes" onPress={() => void save()} loading={updateMutation.isPending} style={{ marginTop: spacing.md }} />
      </ScrollView>
    </ScreenContainer>
  );
};

function SectionTitle({
  label,
  palette,
  typography,
  spacing,
}: {
  label: string;
  palette: { textSecondary: string };
  typography: { caption: { fontSize: number } };
  spacing: { md: number; sm: number };
}) {
  return (
    <Text
      style={{
        color: palette.textSecondary,
        fontSize: typography.caption.fontSize,
        fontWeight: '700',
        letterSpacing: 0.4,
        textTransform: 'uppercase',
        marginTop: spacing.md,
        marginBottom: spacing.sm,
      }}
    >
      {label}
    </Text>
  );
}
