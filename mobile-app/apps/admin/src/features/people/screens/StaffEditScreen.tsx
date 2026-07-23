import { staffApi, useUpdateStaff } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { Image, ScrollView, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'StaffEdit'>;

type FormState = {
  first_name: string;
  middle_name: string;
  last_name: string;
  work_email: string;
  personal_email: string;
  phone_number: string;
  id_number: string;
  gender: string;
  date_of_birth: string;
  residential_address: string;
  emergency_contact_name: string;
  emergency_contact_relationship: string;
  emergency_contact_phone: string;
  bank_name: string;
  bank_branch: string;
  bank_account: string;
  kra_pin: string;
  nssf: string;
  nhif: string;
  basic_salary: string;
};

const emptyForm = (): FormState => ({
  first_name: '',
  middle_name: '',
  last_name: '',
  work_email: '',
  personal_email: '',
  phone_number: '',
  id_number: '',
  gender: '',
  date_of_birth: '',
  residential_address: '',
  emergency_contact_name: '',
  emergency_contact_relationship: '',
  emergency_contact_phone: '',
  bank_name: '',
  bank_branch: '',
  bank_account: '',
  kra_pin: '',
  nssf: '',
  nhif: '',
  basic_salary: '',
});

export const StaffEditScreen: React.FC<Props> = ({ route, navigation }) => {
  const { staffId } = route.params;
  const { spacing, palette, typography, colors, radius } = useTheme();
  const updateMutation = useUpdateStaff(staffId);
  const [form, setForm] = useState<FormState>(emptyForm);
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);

  const setField = <K extends keyof FormState>(key: K, value: FormState[K]) => {
    setForm((prev) => ({ ...prev, [key]: value }));
  };

  useEffect(() => {
    void staffApi.getById(staffId).then((res) => {
      const staff = res.data;
      if (!staff) return;
      setAvatarUrl(staff.avatar ?? null);
      setForm({
        first_name: staff.first_name ?? '',
        middle_name: staff.middle_name ?? '',
        last_name: staff.last_name ?? '',
        work_email: staff.work_email ?? '',
        personal_email: staff.personal_email ?? '',
        phone_number: staff.phone_number ?? staff.phone ?? '',
        id_number: staff.id_number ?? '',
        gender: staff.gender ?? '',
        date_of_birth: staff.date_of_birth ?? '',
        residential_address: staff.residential_address ?? '',
        emergency_contact_name: staff.emergency_contact_name ?? '',
        emergency_contact_relationship: staff.emergency_contact_relationship ?? '',
        emergency_contact_phone: staff.emergency_contact_phone ?? '',
        bank_name: staff.bank_name ?? '',
        bank_branch: staff.bank_branch ?? '',
        bank_account: staff.bank_account ?? '',
        kra_pin: staff.kra_pin ?? '',
        nssf: staff.nssf ?? '',
        nhif: staff.nhif ?? '',
        basic_salary: staff.basic_salary != null ? String(staff.basic_salary) : '',
      });
    });
  }, [staffId]);

  const onPickPhoto = async () => {
    try {
      setUploadingPhoto(true);
      const ImagePicker = await import('expo-image-picker');
      const picked = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        quality: 0.85,
      });
      if (picked.canceled || !picked.assets[0]?.uri) return;
      const asset = picked.assets[0];
      const formData = new FormData();
      formData.append('photo', {
        uri: asset.uri,
        name: asset.fileName ?? 'photo.jpg',
        type: asset.mimeType ?? 'image/jpeg',
      } as unknown as Blob);
      const res = await staffApi.uploadPhoto(staffId, formData);
      if (!res.success) throw new Error(res.message || 'Upload failed');
      setAvatarUrl(res.data?.avatar ?? avatarUrl);
      showSuccess('Photo updated');
    } catch (err) {
      showError('Photo upload failed', err instanceof Error ? err.message : 'Could not upload.');
    } finally {
      setUploadingPhoto(false);
    }
  };

  const save = async () => {
    try {
      const salary = form.basic_salary.trim();
      const payload: Record<string, unknown> = {
        first_name: form.first_name.trim(),
        middle_name: form.middle_name.trim() || null,
        last_name: form.last_name.trim(),
        work_email: form.work_email.trim(),
        personal_email: form.personal_email.trim() || null,
        phone_number: form.phone_number.trim(),
        id_number: form.id_number.trim() || 'N/A',
        gender: form.gender.trim() || null,
        date_of_birth: form.date_of_birth.trim() || null,
        residential_address: form.residential_address.trim() || null,
        emergency_contact_name: form.emergency_contact_name.trim() || null,
        emergency_contact_relationship: form.emergency_contact_relationship.trim() || null,
        emergency_contact_phone: form.emergency_contact_phone.trim() || null,
        bank_name: form.bank_name.trim() || null,
        bank_branch: form.bank_branch.trim() || null,
        bank_account: form.bank_account.trim() || null,
        kra_pin: form.kra_pin.trim() || null,
        nssf: form.nssf.trim() || null,
        nhif: form.nhif.trim() || null,
      };
      if (salary !== '') {
        const parsed = Number(salary.replace(/,/g, ''));
        if (Number.isNaN(parsed)) {
          showError('Validation', 'Basic salary must be a number.');
          return;
        }
        payload.basic_salary = parsed;
      }
      await updateMutation.mutateAsync(payload);
      showSuccess('Saved', 'Staff profile updated.');
      navigation.goBack();
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Update failed.');
    }
  };

  const section = (label: string) => (
    <Text
      style={{
        color: palette.textMuted,
        fontSize: typography.overline.fontSize,
        fontWeight: '700',
        letterSpacing: 0.6,
        textTransform: 'uppercase',
        marginTop: spacing.md,
        marginBottom: spacing.sm,
      }}
    >
      {label}
    </Text>
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Edit staff" onBack={() => navigation.goBack()} />

        <View style={{ alignItems: 'center', marginBottom: spacing.md }}>
          {avatarUrl ? (
            <Image
              source={{ uri: avatarUrl }}
              style={{ width: 88, height: 88, borderRadius: 44, marginBottom: spacing.sm }}
            />
          ) : (
            <View
              style={{
                width: 88,
                height: 88,
                borderRadius: 44,
                backgroundColor: palette.primaryMuted,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: spacing.sm,
              }}
            >
              <Text style={{ color: colors.primary, fontSize: 28, fontWeight: '700' }}>
                {(form.first_name || '?').charAt(0)}
              </Text>
            </View>
          )}
          <Button
            label="Change photo"
            variant="secondary"
            onPress={() => void onPickPhoto()}
            loading={uploadingPhoto}
          />
        </View>

        {section('Identity')}
        <TextField label="First name" value={form.first_name} onChangeText={(v) => setField('first_name', v)} />
        <TextField label="Middle name" value={form.middle_name} onChangeText={(v) => setField('middle_name', v)} />
        <TextField label="Last name" value={form.last_name} onChangeText={(v) => setField('last_name', v)} />
        <TextField label="ID number" value={form.id_number} onChangeText={(v) => setField('id_number', v)} />
        <TextField label="Gender" value={form.gender} onChangeText={(v) => setField('gender', v)} placeholder="male / female / other" />
        <TextField
          label="Date of birth"
          value={form.date_of_birth}
          onChangeText={(v) => setField('date_of_birth', v)}
          placeholder="YYYY-MM-DD"
        />

        {section('Contact')}
        <TextField
          label="Phone"
          value={form.phone_number}
          onChangeText={(v) => setField('phone_number', v)}
          keyboardType="phone-pad"
        />
        <TextField
          label="Work email"
          value={form.work_email}
          onChangeText={(v) => setField('work_email', v)}
          autoCapitalize="none"
          keyboardType="email-address"
        />
        <TextField
          label="Personal email"
          value={form.personal_email}
          onChangeText={(v) => setField('personal_email', v)}
          autoCapitalize="none"
          keyboardType="email-address"
        />
        <TextField
          label="Residential address"
          value={form.residential_address}
          onChangeText={(v) => setField('residential_address', v)}
        />

        {section('Emergency contact')}
        <TextField
          label="Name"
          value={form.emergency_contact_name}
          onChangeText={(v) => setField('emergency_contact_name', v)}
        />
        <TextField
          label="Relationship"
          value={form.emergency_contact_relationship}
          onChangeText={(v) => setField('emergency_contact_relationship', v)}
        />
        <TextField
          label="Phone"
          value={form.emergency_contact_phone}
          onChangeText={(v) => setField('emergency_contact_phone', v)}
          keyboardType="phone-pad"
        />

        {section('Bank & statutory')}
        <TextField label="Bank name" value={form.bank_name} onChangeText={(v) => setField('bank_name', v)} />
        <TextField label="Bank branch" value={form.bank_branch} onChangeText={(v) => setField('bank_branch', v)} />
        <TextField label="Bank account" value={form.bank_account} onChangeText={(v) => setField('bank_account', v)} />
        <TextField label="KRA PIN" value={form.kra_pin} onChangeText={(v) => setField('kra_pin', v)} />
        <TextField label="NSSF" value={form.nssf} onChangeText={(v) => setField('nssf', v)} />
        <TextField label="NHIF / SHIF" value={form.nhif} onChangeText={(v) => setField('nhif', v)} />
        <TextField
          label="Basic salary"
          value={form.basic_salary}
          onChangeText={(v) => setField('basic_salary', v)}
          keyboardType="decimal-pad"
        />

        <Text
          style={{
            color: palette.textMuted,
            fontSize: typography.caption.fontSize,
            marginTop: spacing.sm,
            marginBottom: spacing.md,
          }}
        >
          Documents can be uploaded and viewed on the staff Documents tab.
        </Text>

        <Button
          label="Save changes"
          onPress={() => void save()}
          loading={updateMutation.isPending}
          style={{ marginTop: spacing.sm, borderRadius: radius.control }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
