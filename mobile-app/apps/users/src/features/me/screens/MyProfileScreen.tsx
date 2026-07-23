import {
  accountApi,
  documentsApi,
  downloadAuthenticatedFile,
  queryKeys,
  staffApi,
  useAuth,
  useCurrentUser,
  useStaffDetail,
  useStaffDocuments,
  useUpdateStaff,
} from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import { useQueryClient } from '@tanstack/react-query';
import React, { useEffect, useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

/**
 * Shared profile view/edit for teacher, parent, student, and driver portals.
 * Staff-linked accounts get full HR fields, photo, and documents.
 */
export const MyProfileScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const { logout, refreshUser } = useAuth();
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const staffId = user?.staffId ?? 0;
  const staffQuery = useStaffDetail(staffId, { enabled: staffId > 0 });
  const docsQuery = useStaffDocuments(staffId, { enabled: staffId > 0 });
  const updateStaff = useUpdateStaff(staffId);
  const queryClient = useQueryClient();

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [personalEmail, setPersonalEmail] = useState('');
  const [idNumber, setIdNumber] = useState('');
  const [address, setAddress] = useState('');
  const [gender, setGender] = useState('');
  const [dateOfBirth, setDateOfBirth] = useState('');
  const [emergencyName, setEmergencyName] = useState('');
  const [emergencyRelationship, setEmergencyRelationship] = useState('');
  const [emergencyPhone, setEmergencyPhone] = useState('');
  const [bankName, setBankName] = useState('');
  const [bankBranch, setBankBranch] = useState('');
  const [bankAccount, setBankAccount] = useState('');
  const [kraPin, setKraPin] = useState('');
  const [nssf, setNssf] = useState('');
  const [nhif, setNhif] = useState('');
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingProfile, setSavingProfile] = useState(false);
  const [changingPassword, setChangingPassword] = useState(false);
  const [uploadingDoc, setUploadingDoc] = useState(false);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  useEffect(() => {
    const s = staffQuery.data;
    if (s) {
      const parts = (user?.name ?? s.fullName ?? '').trim().split(/\s+/);
      setFirstName(parts[0] ?? '');
      setLastName(parts.slice(1).join(' ') || '');
      setPhone(s.phone ?? user?.phone ?? '');
      setPersonalEmail(s.personalEmail ?? '');
      setIdNumber(s.idNumber ?? '');
      setAddress(s.residentialAddress ?? '');
      setGender(s.gender ?? '');
      setDateOfBirth(s.dateOfBirth ?? '');
      setEmergencyName(s.emergencyContact?.name ?? '');
      setEmergencyRelationship(s.emergencyContact?.relationship ?? '');
      setEmergencyPhone(s.emergencyContact?.phone ?? '');
      setBankName(s.bankName ?? '');
      setBankBranch(s.bankBranch ?? '');
      setBankAccount(s.bankAccount ?? '');
      setKraPin(s.kraPin ?? '');
      setNssf(s.nssf ?? '');
      setNhif(s.nhif ?? '');
      return;
    }
    if (user?.phone) setPhone(user.phone);
  }, [staffQuery.data, user?.phone, user?.name]);

  const onPickPhoto = async () => {
    if (!staffId) return;
    try {
      const ImagePicker = await import('expo-image-picker');
      const picked = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        quality: 0.85,
      });
      if (picked.canceled || !picked.assets[0]?.uri) return;
      const asset = picked.assets[0];
      const form = new FormData();
      form.append('photo', {
        uri: asset.uri,
        name: asset.fileName ?? 'photo.jpg',
        type: asset.mimeType ?? 'image/jpeg',
      } as unknown as Blob);
      const res = await staffApi.uploadPhoto(staffId, form);
      if (!res.success) throw new Error(res.message || 'Upload failed');
      showSuccess('Photo updated');
      void staffQuery.refetch();
      void refreshUser();
    } catch (err) {
      showError('Photo upload failed', (err as Error).message);
    }
  };

  const onUploadDocument = async () => {
    if (!staffId) return;
    try {
      const DocumentPicker = await import('expo-document-picker');
      const result = await DocumentPicker.getDocumentAsync({
        type: [
          'application/pdf',
          'image/*',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        copyToCacheDirectory: true,
      });
      if (result.canceled || !result.assets?.[0]) return;
      const asset = result.assets[0];
      setUploadingDoc(true);
      const form = new FormData();
      form.append('title', (asset.name ?? 'Document').replace(/\.[^.]+$/, ''));
      form.append('document_type', 'other');
      form.append('file', {
        uri: asset.uri,
        name: asset.name ?? 'document',
        type: asset.mimeType ?? 'application/octet-stream',
      } as unknown as Blob);
      const res = await documentsApi.uploadStaffDocument(staffId, form);
      if (!res.success) throw new Error(res.message || 'Upload failed');
      showSuccess('Document uploaded');
      await queryClient.invalidateQueries({ queryKey: queryKeys.documents.staff(staffId) });
    } catch (err) {
      showError('Upload failed', (err as Error).message);
    } finally {
      setUploadingDoc(false);
    }
  };

  const saveProfile = async () => {
    if (!staffId) return;
    setSavingProfile(true);
    try {
      await updateStaff.mutateAsync({
        first_name: firstName.trim() || undefined,
        last_name: lastName.trim() || undefined,
        phone_number: phone.trim(),
        personal_email: personalEmail.trim() || null,
        id_number: idNumber.trim() || staffQuery.data?.idNumber || 'N/A',
        residential_address: address.trim() || null,
        gender: gender.trim() || null,
        date_of_birth: dateOfBirth.trim() || null,
        emergency_contact_name: emergencyName.trim() || null,
        emergency_contact_relationship: emergencyRelationship.trim() || null,
        emergency_contact_phone: emergencyPhone.trim() || null,
        bank_name: bankName.trim() || null,
        bank_branch: bankBranch.trim() || null,
        bank_account: bankAccount.trim() || null,
        kra_pin: kraPin.trim() || null,
        nssf: nssf.trim() || null,
        nhif: nhif.trim() || null,
      });
      showSuccess(
        'Submitted',
        'Your profile changes were submitted and are pending admin approval (same as web).',
      );
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not save profile.');
    } finally {
      setSavingProfile(false);
    }
  };

  const changePassword = async () => {
    if (!newPassword || newPassword !== confirmPassword) {
      showError('Password', 'New passwords do not match.');
      return;
    }
    setChangingPassword(true);
    try {
      const res = await accountApi.changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });
      if (!res.success) throw new Error(res.message || 'Password change failed.');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      showSuccess('Password changed', 'Your password has been updated.');
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not change password.');
    } finally {
      setChangingPassword(false);
    }
  };

  const sectionHeader = (label: string) => (
    <Text
      style={{
        color: palette.textMuted,
        fontSize: typography.overline?.fontSize ?? 11,
        fontWeight: '700',
        letterSpacing: 0.6,
        textTransform: 'uppercase',
        marginBottom: spacing.sm,
        marginTop: spacing.md,
      }}
    >
      {label}
    </Text>
  );

  const groupStyle = [
    elevation?.[1] ?? {},
    {
      borderRadius: radius.lg,
      backgroundColor: palette.surface,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: palette.border,
      padding: spacing.md,
      marginBottom: spacing.sm,
    },
  ];

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="My profile"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />

        <View style={[groupStyle, { alignItems: 'center' }]}>
          {user?.avatarUrl || staffQuery.data?.avatarUrl ? (
            <Image
              source={{ uri: (user?.avatarUrl ?? staffQuery.data?.avatarUrl) as string }}
              style={{ width: 88, height: 88, borderRadius: 44, marginBottom: spacing.sm }}
            />
          ) : (
            <View
              style={{
                width: 88,
                height: 88,
                borderRadius: 44,
                backgroundColor: colors.primary,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: spacing.sm,
              }}
            >
              <Text style={{ color: '#fff', fontSize: 32, fontWeight: '700' }}>
                {user?.name?.charAt(0) ?? '?'}
              </Text>
            </View>
          )}
          <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '700' }}>
            {user?.name ?? '—'}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: spacing.xs }}>
            {user?.roleName ?? user?.role ?? 'User'}
          </Text>
          {staffId > 0 ? (
            <Button
              label="Change photo"
              variant="secondary"
              onPress={() => void onPickPhoto()}
              style={{ marginTop: spacing.sm }}
            />
          ) : null}
        </View>

        {sectionHeader('Account')}
        <View style={groupStyle}>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
            Email
          </Text>
          <Text style={{ color: palette.textPrimary, marginBottom: staffId > 0 ? spacing.md : 0 }}>
            {user?.email ?? '—'}
          </Text>
          {staffId > 0 ? (
            <>
              <TextField label="First name" value={firstName} onChangeText={setFirstName} />
              <TextField label="Last name" value={lastName} onChangeText={setLastName} />
              <TextField label="Phone" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
              <TextField
                label="Personal email"
                value={personalEmail}
                onChangeText={setPersonalEmail}
                autoCapitalize="none"
                keyboardType="email-address"
              />
              <TextField label="ID number" value={idNumber} onChangeText={setIdNumber} />
              <TextField label="Gender" value={gender} onChangeText={setGender} />
              <TextField
                label="Date of birth"
                value={dateOfBirth}
                onChangeText={setDateOfBirth}
                placeholder="YYYY-MM-DD"
              />
              <TextField label="Residential address" value={address} onChangeText={setAddress} />
            </>
          ) : (
            <TextField label="Phone" value={phone} onChangeText={setPhone} keyboardType="phone-pad" editable={false} />
          )}
        </View>

        {staffId > 0 ? (
          <>
            {sectionHeader('Emergency contact')}
            <View style={groupStyle}>
              <TextField label="Name" value={emergencyName} onChangeText={setEmergencyName} />
              <TextField
                label="Relationship"
                value={emergencyRelationship}
                onChangeText={setEmergencyRelationship}
              />
              <TextField
                label="Phone"
                value={emergencyPhone}
                onChangeText={setEmergencyPhone}
                keyboardType="phone-pad"
              />
            </View>

            {sectionHeader('Bank & statutory')}
            <View style={groupStyle}>
              <TextField label="Bank name" value={bankName} onChangeText={setBankName} />
              <TextField label="Bank branch" value={bankBranch} onChangeText={setBankBranch} />
              <TextField label="Bank account" value={bankAccount} onChangeText={setBankAccount} />
              <TextField label="KRA PIN" value={kraPin} onChangeText={setKraPin} />
              <TextField label="NSSF" value={nssf} onChangeText={setNssf} />
              <TextField label="NHIF / SHIF" value={nhif} onChangeText={setNhif} />
              <Button
                label="Submit profile changes"
                onPress={() => void saveProfile()}
                loading={savingProfile || updateStaff.isPending}
                style={{ marginTop: spacing.sm }}
              />
            </View>

            {sectionHeader('Documents')}
            <View style={groupStyle}>
              <Button
                label="Upload document"
                variant="secondary"
                onPress={() => void onUploadDocument()}
                loading={uploadingDoc}
              />
              {(docsQuery.data ?? []).length === 0 ? (
                <Text style={{ color: palette.textMuted, marginTop: spacing.sm }}>No documents yet.</Text>
              ) : (
                (docsQuery.data ?? []).map((doc) => (
                  <View key={doc.id} style={{ marginTop: spacing.sm }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{doc.title}</Text>
                    {doc.download_path ? (
                      <Pressable
                        onPress={() => {
                          void (async () => {
                            setDownloadingId(doc.id);
                            try {
                              await downloadAuthenticatedFile(doc.download_path!, doc.title);
                            } catch (err) {
                              showError('Download failed', (err as Error).message);
                            } finally {
                              setDownloadingId(null);
                            }
                          })();
                        }}
                      >
                        <Text style={{ color: colors.primary, fontWeight: '600', marginTop: 4 }}>
                          {downloadingId === doc.id ? 'Opening…' : 'View / download'}
                        </Text>
                      </Pressable>
                    ) : null}
                  </View>
                ))
              )}
            </View>
          </>
        ) : null}

        {sectionHeader('Security')}
        <View style={groupStyle}>
          <TextField
            label="Current password"
            value={currentPassword}
            onChangeText={setCurrentPassword}
            secureTextEntry
          />
          <TextField label="New password" value={newPassword} onChangeText={setNewPassword} secureTextEntry />
          <TextField
            label="Confirm new password"
            value={confirmPassword}
            onChangeText={setConfirmPassword}
            secureTextEntry
          />
          <Button
            label="Update password"
            variant="secondary"
            onPress={() => void changePassword()}
            loading={changingPassword}
            style={{ marginTop: spacing.sm }}
          />
        </View>

        <Button label="Sign out" variant="ghost" onPress={logout} style={{ marginTop: spacing.md }} />
      </ScrollView>
    </ScreenContainer>
  );
};
