import {
  useAuth,
  useCurrentUser,
  useStaffDetail,
  useUpdateStaff,
  accountApi,
  staffApi,
} from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import * as ImagePicker from 'expo-image-picker';
import React, { useEffect, useState } from 'react';
import { Image, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { navigateDashboardBack } from '../../../navigation/navigateWorkspace';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<DashboardStackParamList, 'UserProfile'>;

export const UserProfileScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const user = useCurrentUser();
  const { refreshUser } = useAuth();
  const staffId = user?.staffId ?? 0;
  const staffQuery = useStaffDetail(staffId, { enabled: staffId > 0 });
  const updateStaff = useUpdateStaff(staffId);

  const [phone, setPhone] = useState('');
  const [personalEmail, setPersonalEmail] = useState('');
  const [address, setAddress] = useState('');
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingProfile, setSavingProfile] = useState(false);
  const [changingPassword, setChangingPassword] = useState(false);

  useEffect(() => {
    const s = staffQuery.data;
    if (!s) return;
    setPhone(s.phone ?? user?.phone ?? '');
    setPersonalEmail(s.personalEmail ?? '');
    setAddress(s.residentialAddress ?? '');
  }, [staffQuery.data, user?.phone]);

  const onPickPhoto = async () => {
    if (!staffId) return;
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
    try {
      const res = await staffApi.uploadPhoto(staffId, form);
      if (!res.success) throw new Error(res.message || 'Upload failed.');
      await refreshUser();
      showSuccess('Photo updated', 'Your profile photo has been updated.');
    } catch (err) {
      showError('Upload failed', err instanceof Error ? err.message : 'Could not upload photo.');
    }
  };

  const saveProfile = async () => {
    if (!staffId) return;
    setSavingProfile(true);
    try {
      await updateStaff.mutateAsync({
        phone_number: phone.trim(),
        personal_email: personalEmail.trim() || null,
        residential_address: address.trim() || null,
        id_number: staffQuery.data?.idNumber ?? undefined,
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
        fontSize: typography.overline.fontSize,
        fontWeight: typography.overline.fontWeight,
        letterSpacing: typography.overline.letterSpacing,
        textTransform: 'uppercase',
        marginBottom: spacing.sm,
        marginTop: spacing.md,
      }}
    >
      {label}
    </Text>
  );

  const groupStyle = [
    elevation[1],
    {
      borderRadius: radius.card,
      backgroundColor: palette.surfaceRaised,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: palette.borderSubtle,
      padding: spacing.md,
      marginBottom: spacing.sm,
    },
  ];

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="My profile"
          onBack={() => navigateDashboardBack(navigation)}
        />

        <View style={[groupStyle, { alignItems: 'center', marginTop: spacing.sm }]}>
          {user?.avatarUrl ? (
            <Image
              source={{ uri: user.avatarUrl }}
              style={{ width: 88, height: 88, borderRadius: 44, marginBottom: spacing.sm }}
            />
          ) : (
            <View
              style={{
                width: 88,
                height: 88,
                borderRadius: 44,
                backgroundColor: palette.accent,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: spacing.sm,
              }}
            >
              <Text
                style={{
                  color: colors.primary,
                  fontWeight: typography.display.fontWeight,
                  fontSize: typography.display.fontSize,
                }}
              >
                {user?.name?.charAt(0) ?? '?'}
              </Text>
            </View>
          )}
          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: typography.title.fontWeight,
              fontSize: typography.title.fontSize,
            }}
          >
            {user?.name ?? '—'}
          </Text>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
            }}
          >
            {user?.roleName ?? user?.role ?? 'Staff'}
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
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
            }}
          >
            Work email (admin-managed)
          </Text>
          <Text
            style={{
              color: palette.textPrimary,
              fontSize: typography.body.fontSize,
              marginBottom: staffId > 0 ? spacing.md : 0,
            }}
          >
            {user?.email ?? '—'}
          </Text>

          {staffId > 0 ? (
            <>
              <TextField label="Phone" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
              <TextField
                label="Personal email"
                value={personalEmail}
                onChangeText={setPersonalEmail}
                keyboardType="email-address"
                autoCapitalize="none"
              />
              <TextField label="Residential address" value={address} onChangeText={setAddress} />
              <Button
                label="Submit profile changes"
                onPress={() => void saveProfile()}
                loading={savingProfile || updateStaff.isPending}
                style={{ marginTop: spacing.sm }}
              />
            </>
          ) : null}
        </View>

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
      </ScrollView>
    </ScreenContainer>
  );
};
