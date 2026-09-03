import {
  emptyKemisLearnerValues,
  emptyKemisParentSlotValues,
  formatLearnerInterests,
  formatOrphanStatus,
  formatParentSlotName,
  kemisLearnerFromApi,
  kemisLearnerPayload,
  kemisParentSlotFromApi,
  kemisParentSlotPayload,
  useKemisOptions,
  useParentProfileReview,
  useUpdateParentProfileReview,
  downloadAuthenticatedFile,
  type KemisLearnerValues,
  type KemisParentSlotValues,
  type ProfileReviewUpdatePayload,
} from '@erp/core';
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
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<ParentStackParamList>;
type Route = RouteProp<ParentStackParamList, 'ChildProfile'>;

/**
 * In-app child + family profile view/edit (same data as profile-review API).
 * Replaces opening the public family-update link in a browser.
 */
export const ChildProfileScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const studentId = route.params.studentId;
  const { palette, spacing, typography, radius, colors } = useTheme();
  const query = useParentProfileReview();
  const save = useUpdateParentProfileReview();
  const kemisOptionsQuery = useKemisOptions();

  const [editing, setEditing] = useState(false);
  const [firstName, setFirstName] = useState('');
  const [middleName, setMiddleName] = useState('');
  const [lastName, setLastName] = useState('');
  const [gender, setGender] = useState('');
  const [dob, setDob] = useState('');
  const [hasAllergies, setHasAllergies] = useState(false);
  const [allergiesNotes, setAllergiesNotes] = useState('');
  const [immunized, setImmunized] = useState(false);
  const [kemisLearner, setKemisLearner] = useState<KemisLearnerValues>(emptyKemisLearnerValues());
  const [residentialArea, setResidentialArea] = useState('');
  const [preferredHospital, setPreferredHospital] = useState('');
  const [emergencyName, setEmergencyName] = useState('');
  const [emergencyPhone, setEmergencyPhone] = useState('');
  const [father, setFather] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [mother, setMother] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [guardian, setGuardian] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [guardianRelationship, setGuardianRelationship] = useState('');

  const student = useMemo(
    () => query.data?.students.find((s) => s.id === studentId) ?? null,
    [query.data, studentId],
  );
  const className = student?.class_name ?? null;
  const admission = student?.admission_number ?? null;
  const kemisOptions = kemisOptionsQuery.data;

  useEffect(() => {
    if (!query.data || !student) return;
    setFirstName(student.first_name ?? '');
    setMiddleName(student.middle_name ?? '');
    setLastName(student.last_name ?? '');
    setGender(student.gender ?? '');
    setDob(student.dob ?? '');
    setHasAllergies(student.has_allergies);
    setAllergiesNotes(student.allergies_notes ?? '');
    setImmunized(student.is_fully_immunized);
    setKemisLearner(kemisLearnerFromApi(student as Record<string, unknown>));
    setResidentialArea(student.residential_area ?? '');
    setPreferredHospital(student.preferred_hospital ?? '');
    setEmergencyName(student.emergency_contact_name ?? '');
    setEmergencyPhone(student.emergency_contact_phone ?? '');
    const p = query.data.parent as Record<string, unknown>;
    setFather(kemisParentSlotFromApi(p, 'father'));
    setMother(kemisParentSlotFromApi(p, 'mother'));
    setGuardian(kemisParentSlotFromApi(p, 'guardian'));
    setGuardianRelationship(String(p.guardian_relationship ?? ''));
  }, [query.data, student]);

  const onSave = async () => {
    if (!firstName.trim() || !lastName.trim()) {
      showError('Name required', 'First and last name are required.');
      return;
    }
    const payload: ProfileReviewUpdatePayload = {
      residential_area: residentialArea.trim() || null,
      preferred_hospital: preferredHospital.trim() || null,
      emergency_contact_name: emergencyName.trim() || null,
      emergency_contact_phone: emergencyPhone.trim() || null,
      guardian_relationship: guardianRelationship.trim() || null,
      ...kemisParentSlotPayload('father', father),
      ...kemisParentSlotPayload('mother', mother),
      ...kemisParentSlotPayload('guardian', guardian),
      students: [
        {
          id: studentId,
          first_name: firstName.trim(),
          middle_name: middleName.trim() || null,
          last_name: lastName.trim(),
          gender: gender.trim() || null,
          dob: dob.trim() || null,
          has_allergies: hasAllergies,
          allergies_notes: allergiesNotes.trim() || null,
          is_fully_immunized: immunized,
          ...kemisLearnerPayload(kemisLearner),
        },
      ],
    };
    try {
      await save.mutateAsync(payload);
      showSuccess('Saved', 'Child and family details were updated.');
      setEditing(false);
      void query.refetch();
    } catch (err) {
      showError('Could not save', err instanceof Error ? err.message : 'Please try again.');
    }
  };

  const sectionTitle = (title: string) => (
    <Text
      style={{
        color: palette.textSecondary,
        fontWeight: '700',
        fontSize: typography.caption.fontSize,
        textTransform: 'uppercase',
        letterSpacing: 0.4,
        marginTop: spacing.lg,
        marginBottom: spacing.sm,
      }}
    >
      {title}
    </Text>
  );

  const readRow = (label: string, value: string) => (
    <View style={[styles.readRow, { borderBottomColor: palette.borderSubtle }]}>
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>{label}</Text>
      <Text style={{ color: palette.textPrimary, fontWeight: '600', marginTop: 2 }}>{value || '—'}</Text>
    </View>
  );

  const cardStyle = {
    backgroundColor: palette.surface,
    borderColor: palette.border,
    borderWidth: 1,
    borderRadius: radius.lg,
    paddingHorizontal: spacing.md,
  };

  const parentReadRow = (label: string, slot: KemisParentSlotValues, relationship?: string) => {
    const name = formatParentSlotName(slot);
    const details = [
      name,
      slot.id_type && slot.id_number ? `${slot.id_type}: ${slot.id_number}` : null,
      slot.country_of_residence,
      slot.phone,
      slot.email,
      relationship,
    ]
      .filter(Boolean)
      .join(' · ');
    if (!details) return null;
    return readRow(label, details);
  };

  if (query.isLoading || kemisOptionsQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Child profile" onBack={() => navigation.goBack()} />
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.xl }} />
      </ScreenContainer>
    );
  }

  if (!student || !kemisOptions) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Child profile" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary, marginTop: spacing.md }}>
          This child is not linked to your parent account.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={editing ? 'Edit profile' : 'Child profile'}
        subtitle={[admission, className].filter(Boolean).join(' · ') || undefined}
        onBack={() => navigation.goBack()}
      />

      <View style={{ flexDirection: 'row', justifyContent: 'flex-end', marginBottom: spacing.sm }}>
        <Pressable
          onPress={() => setEditing((v) => !v)}
          style={{
            paddingHorizontal: spacing.md,
            paddingVertical: spacing.sm,
            borderRadius: radius.full,
            backgroundColor: editing ? palette.surfaceMuted : palette.primaryMuted,
          }}
        >
          <Text style={{ color: palette.primary, fontWeight: '700' }}>{editing ? 'Cancel' : 'Edit'}</Text>
        </Pressable>
      </View>

      {sectionTitle('Child')}
      {editing ? (
        <>
          <TextField label="First name" value={firstName} onChangeText={setFirstName} />
          <TextField label="Middle name" value={middleName} onChangeText={setMiddleName} />
          <TextField label="Last name" value={lastName} onChangeText={setLastName} />
          <FilterChipRow label="Gender">
            {(['Male', 'Female'] as const).map((g) => (
              <FilterChip
                key={g}
                label={g}
                active={gender.toLowerCase() === g.toLowerCase()}
                onPress={() => setGender(g)}
              />
            ))}
          </FilterChipRow>
          <TextField label="Date of birth (YYYY-MM-DD)" value={dob} onChangeText={setDob} />
          <FilterChipRow label="Allergies">
            <FilterChip label="No" active={!hasAllergies} onPress={() => setHasAllergies(false)} />
            <FilterChip label="Yes" active={hasAllergies} onPress={() => setHasAllergies(true)} />
          </FilterChipRow>
          {hasAllergies ? (
            <TextField label="Allergy notes" value={allergiesNotes} onChangeText={setAllergiesNotes} multiline />
          ) : null}
          <FilterChipRow label="Fully immunized">
            <FilterChip label="No" active={!immunized} onPress={() => setImmunized(false)} />
            <FilterChip label="Yes" active={immunized} onPress={() => setImmunized(true)} />
          </FilterChipRow>
        </>
      ) : (
        <View style={cardStyle}>
          {readRow('Name', [firstName, middleName, lastName].filter(Boolean).join(' '))}
          {readRow('Gender', gender)}
          {readRow('Date of birth', dob)}
          {readRow('Allergies', hasAllergies ? allergiesNotes || 'Yes' : 'None')}
          {readRow('Immunized', immunized ? 'Yes' : 'No')}
          {readRow('Class', className ?? '')}
        </View>
      )}

      {sectionTitle('Birth & registration')}
      {editing ? (
        <KemisLearnerFields values={kemisLearner} onChange={setKemisLearner} options={kemisOptions} />
      ) : (
        <View style={cardStyle}>
          {readRow('Nationality', kemisLearner.nationality)}
          {readRow('County of birth', kemisLearner.county_of_birth)}
          {readRow('Sub-county of birth', kemisLearner.sub_county_of_birth)}
          {readRow('Location of birth', kemisLearner.location_of_birth)}
          {readRow('Birth certificate entry no.', kemisLearner.birth_certificate_entry_no)}
          {readRow('Medical condition', kemisLearner.medical_condition)}
          {readRow('Religion', kemisLearner.religion)}
          {readRow('Orphan status', formatOrphanStatus(kemisLearner.orphan_status, kemisOptions))}
          {readRow('Special needs', kemisLearner.has_special_needs ? 'Yes' : 'No')}
          {kemisLearner.has_special_needs ? readRow('Disability type', kemisLearner.disability_type) : null}
          {readRow(
            'Learner interests',
            formatLearnerInterests(kemisLearner.learner_interests, kemisLearner.learner_interests_other),
          )}
        </View>
      )}

      {sectionTitle('Household')}
      {editing ? (
        <>
          <TextField label="Residential area" value={residentialArea} onChangeText={setResidentialArea} />
          <TextField label="Preferred hospital" value={preferredHospital} onChangeText={setPreferredHospital} />
          <TextField label="Emergency contact name" value={emergencyName} onChangeText={setEmergencyName} />
          <TextField
            label="Emergency contact phone"
            value={emergencyPhone}
            onChangeText={setEmergencyPhone}
            keyboardType="phone-pad"
          />
        </>
      ) : (
        <View style={cardStyle}>
          {readRow('Residential area', residentialArea)}
          {readRow('Preferred hospital', preferredHospital)}
          {readRow('Emergency contact', [emergencyName, emergencyPhone].filter(Boolean).join(' · '))}
        </View>
      )}

      {sectionTitle('Parents / guardians')}
      {editing ? (
        <>
          <KemisParentIdentityFields slot="father" title="Father" values={father} onChange={setFather} options={kemisOptions} />
          <KemisParentIdentityFields slot="mother" title="Mother" values={mother} onChange={setMother} options={kemisOptions} />
          <KemisParentIdentityFields
            slot="guardian"
            title="Guardian"
            values={guardian}
            onChange={setGuardian}
            options={kemisOptions}
            showRelationship
            relationship={guardianRelationship}
            onRelationshipChange={setGuardianRelationship}
          />
        </>
      ) : (
        <View style={cardStyle}>
          {parentReadRow('Father', father)}
          {parentReadRow('Mother', mother)}
          {parentReadRow('Guardian', guardian, guardianRelationship)}
        </View>
      )}

      {sectionTitle('Family documents')}
      <View style={cardStyle}>
        {(query.data?.documents ?? []).length === 0 ? (
          <Text style={{ color: palette.textMuted }}>No parent documents on file.</Text>
        ) : (
          (query.data?.documents ?? []).map((doc) => (
            <Pressable
              key={doc.id}
              onPress={() => {
                void downloadAuthenticatedFile(doc.download_path, doc.title ?? 'document').catch((err) =>
                  showError('Download failed', (err as Error).message),
                );
              }}
              style={styles.readRow}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{doc.title ?? doc.file_name}</Text>
              <Text style={{ color: colors.primary, marginTop: 4 }}>View / download</Text>
            </Pressable>
          ))
        )}
      </View>

      {editing ? (
        <Button
          label={save.isPending ? 'Saving…' : 'Save changes'}
          onPress={() => void onSave()}
          disabled={save.isPending}
          style={{ marginTop: spacing.lg }}
        />
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  readRow: {
    paddingVertical: 10,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
