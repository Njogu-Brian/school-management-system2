import {
  useAuth,
  useCompleteParentProfileReview,
  useParentProfileReview,
  useUpdateParentProfileReview,
  type ProfileReviewUpdatePayload,
} from '@erp/core';
import { Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

interface StudentForm {
  id: number;
  first_name: string;
  middle_name: string;
  last_name: string;
  dob: string;
  has_allergies: boolean;
  allergies_notes: string;
  is_fully_immunized: boolean;
}

/**
 * Forced one-time profile review after a parent claims their account (data only — no
 * document uploads). Saving + completing clears `parent_profile_review_required` and the
 * root gate then renders the normal shell.
 */
export const ParentProfileReviewScreen: React.FC = () => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const { refreshUser, logout } = useAuth();
  const query = useParentProfileReview();
  const save = useUpdateParentProfileReview();
  const complete = useCompleteParentProfileReview();

  const [parent, setParent] = useState({
    father_name: '',
    father_id_number: '',
    father_phone: '',
    father_email: '',
    mother_name: '',
    mother_id_number: '',
    mother_phone: '',
    mother_email: '',
    guardian_name: '',
    guardian_phone: '',
    guardian_relationship: '',
  });
  const [students, setStudents] = useState<StudentForm[]>([]);

  useEffect(() => {
    if (!query.data) return;
    const p = query.data.parent;
    setParent({
      father_name: p.father_name ?? '',
      father_id_number: p.father_id_number ?? '',
      father_phone: p.father_phone ?? '',
      father_email: p.father_email ?? '',
      mother_name: p.mother_name ?? '',
      mother_id_number: p.mother_id_number ?? '',
      mother_phone: p.mother_phone ?? '',
      mother_email: p.mother_email ?? '',
      guardian_name: p.guardian_name ?? '',
      guardian_phone: p.guardian_phone ?? '',
      guardian_relationship: p.guardian_relationship ?? '',
    });
    setStudents(
      query.data.students.map((s) => ({
        id: s.id,
        first_name: s.first_name ?? '',
        middle_name: s.middle_name ?? '',
        last_name: s.last_name ?? '',
        dob: s.dob ?? '',
        has_allergies: s.has_allergies,
        allergies_notes: s.allergies_notes ?? '',
        is_fully_immunized: s.is_fully_immunized,
      })),
    );
  }, [query.data]);

  const setParentField = (key: keyof typeof parent, value: string) =>
    setParent((prev) => ({ ...prev, [key]: value }));

  const setStudentField = <K extends keyof StudentForm>(id: number, key: K, value: StudentForm[K]) =>
    setStudents((prev) => prev.map((s) => (s.id === id ? { ...s, [key]: value } : s)));

  const payload = useMemo<ProfileReviewUpdatePayload>(
    () => ({
      ...parent,
      students: students.map((s) => ({
        id: s.id,
        first_name: s.first_name,
        middle_name: s.middle_name || null,
        last_name: s.last_name,
        dob: s.dob || null,
        has_allergies: s.has_allergies,
        allergies_notes: s.allergies_notes || null,
        is_fully_immunized: s.is_fully_immunized,
      })),
    }),
    [parent, students],
  );

  const handleSaveAndFinish = async () => {
    try {
      await save.mutateAsync(payload);
      await complete.mutateAsync();
      await refreshUser();
      showSuccess('All set', 'Your details have been saved.');
    } catch (err) {
      showError('Could not save', err instanceof Error ? err.message : 'Please try again.');
    }
  };

  if (query.isLoading) {
    return (
      <ScreenContainer edges={['top', 'bottom']}>
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      </ScreenContainer>
    );
  }

  const busy = save.isPending || complete.isPending;

  const Checkbox: React.FC<{ label: string; value: boolean; onToggle: () => void }> = ({ label, value, onToggle }) => (
    <Pressable
      onPress={onToggle}
      style={{ flexDirection: 'row', alignItems: 'center', marginVertical: spacing.sm }}
    >
      <View
        style={{
          width: 22,
          height: 22,
          borderRadius: 6,
          borderWidth: 2,
          borderColor: value ? colors.primary : palette.border,
          backgroundColor: value ? colors.primary : 'transparent',
          alignItems: 'center',
          justifyContent: 'center',
          marginRight: spacing.sm,
        }}
      >
        {value ? <Ionicons name="checkmark" size={14} color="#fff" /> : null}
      </View>
      <Text style={{ color: palette.textPrimary }}>{label}</Text>
    </Pressable>
  );

  return (
    <ScreenContainer
      scroll
      edges={['top', 'bottom']}
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
    >
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.md }}>
        <Text style={{ color: palette.textPrimary, fontSize: typography.headlineLarge.fontSize, fontWeight: '800' }}>
          Review your details
        </Text>
        <Pressable onPress={() => void logout()} hitSlop={8}>
          <Text style={{ color: colors.error, fontWeight: '600' }}>Sign out</Text>
        </Pressable>
      </View>
      <Text style={{ color: palette.textSecondary, marginBottom: spacing.lg }}>
        Please confirm your family details before continuing. You can update these anytime later.
      </Text>

      <View
        style={{
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          borderColor: palette.border,
          borderWidth: 1,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Father / Parent 1</Text>
        <TextField label="Full name" value={parent.father_name} onChangeText={(t) => setParentField('father_name', t)} />
        <TextField label="ID number" value={parent.father_id_number} onChangeText={(t) => setParentField('father_id_number', t)} />
        <TextField label="Phone" value={parent.father_phone} onChangeText={(t) => setParentField('father_phone', t)} keyboardType="phone-pad" />
        <TextField label="Email" value={parent.father_email} onChangeText={(t) => setParentField('father_email', t)} autoCapitalize="none" keyboardType="email-address" />
      </View>

      <View
        style={{
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          borderColor: palette.border,
          borderWidth: 1,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Mother / Parent 2</Text>
        <TextField label="Full name" value={parent.mother_name} onChangeText={(t) => setParentField('mother_name', t)} />
        <TextField label="ID number" value={parent.mother_id_number} onChangeText={(t) => setParentField('mother_id_number', t)} />
        <TextField label="Phone" value={parent.mother_phone} onChangeText={(t) => setParentField('mother_phone', t)} keyboardType="phone-pad" />
        <TextField label="Email" value={parent.mother_email} onChangeText={(t) => setParentField('mother_email', t)} autoCapitalize="none" keyboardType="email-address" />
      </View>

      <View
        style={{
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          borderColor: palette.border,
          borderWidth: 1,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Guardian (if any)</Text>
        <TextField label="Full name" value={parent.guardian_name} onChangeText={(t) => setParentField('guardian_name', t)} />
        <TextField label="Phone" value={parent.guardian_phone} onChangeText={(t) => setParentField('guardian_phone', t)} keyboardType="phone-pad" />
        <TextField label="Relationship" value={parent.guardian_relationship} onChangeText={(t) => setParentField('guardian_relationship', t)} />
      </View>

      {students.map((s) => (
        <View
          key={s.id}
          style={{
            backgroundColor: palette.surface,
            borderRadius: radius.lg,
            borderColor: palette.border,
            borderWidth: 1,
            padding: spacing.md,
            marginBottom: spacing.md,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
            {[s.first_name, s.last_name].filter(Boolean).join(' ') || 'Student'}
          </Text>
          <TextField label="First name" value={s.first_name} onChangeText={(t) => setStudentField(s.id, 'first_name', t)} />
          <TextField label="Middle name" value={s.middle_name} onChangeText={(t) => setStudentField(s.id, 'middle_name', t)} />
          <TextField label="Last name" value={s.last_name} onChangeText={(t) => setStudentField(s.id, 'last_name', t)} />
          <TextField label="Date of birth (YYYY-MM-DD)" value={s.dob} onChangeText={(t) => setStudentField(s.id, 'dob', t)} placeholder="2015-04-20" />
          <Checkbox label="Has allergies" value={s.has_allergies} onToggle={() => setStudentField(s.id, 'has_allergies', !s.has_allergies)} />
          {s.has_allergies ? (
            <TextField label="Allergy notes" value={s.allergies_notes} onChangeText={(t) => setStudentField(s.id, 'allergies_notes', t)} multiline />
          ) : null}
          <Checkbox label="Fully immunized" value={s.is_fully_immunized} onToggle={() => setStudentField(s.id, 'is_fully_immunized', !s.is_fully_immunized)} />
        </View>
      ))}

      <Button label="Save & continue" onPress={() => void handleSaveAndFinish()} loading={busy} style={{ marginTop: spacing.sm }} />
    </ScreenContainer>
  );
};
