import { staffApi, type StaffDetail } from '@erp/core';
import { Button, StaffFieldSection, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Text, View } from 'react-native';
import { capitalizeStatus } from '../utils/formatters';
import { confirmAction, showError, showSuccess } from '../../../shared/utils/feedback';

export interface EmploymentTabProps {
  staff: StaffDetail;
  canViewFinance: boolean;
}

export const EmploymentTab: React.FC<EmploymentTabProps> = ({ staff, canViewFinance }) => {
  const { spacing, palette, typography } = useTheme();
  const [busy, setBusy] = useState(false);
  const exemptions =
    staff.statutoryExemptions.length > 0 ? staff.statutoryExemptions.join(', ') : null;

  const resetAndShare = (option: 'id_number' | 'random') => {
    confirmAction(
      'Reset login password',
      option === 'id_number'
        ? 'Reset password to ID number and show it so you can share with the staff member?'
        : 'Generate a random temporary password and show it for sharing?',
      'Reset',
      async () => {
        setBusy(true);
        try {
          const res = await staffApi.resetPassword(staff.id, {
            password_option: option,
            share: true,
          });
          if (!res.success || !res.data) throw new Error(res.message || 'Reset failed.');
          showSuccess(
            'Password reset',
            `Login: ${res.data.login}\nTemporary password: ${res.data.temporary_password}\nThey must change it on next sign-in. App PIN stays on their device — they can reset it in Settings.`,
          );
        } catch (err) {
          showError('Reset failed', err instanceof Error ? err.message : 'Could not reset password.');
        } finally {
          setBusy(false);
        }
      },
      true,
    );
  };

  const resend = () => {
    confirmAction(
      'Resend credentials',
      'Send login details via configured email/SMS templates?',
      'Send',
      async () => {
        setBusy(true);
        try {
          const res = await staffApi.resendCredentials(staff.id);
          if (!res.success) throw new Error(res.message || 'Send failed.');
          showSuccess('Sent', res.message ?? 'Credentials shared where templates allow.');
        } catch (err) {
          showError('Send failed', err instanceof Error ? err.message : 'Could not resend credentials.');
        } finally {
          setBusy(false);
        }
      },
      true,
    );
  };

  const forceChangeOnNextLogin = () => {
    confirmAction(
      'Require password change',
      'Ask this staff member to set a new password the next time they sign in? Their current password still works until then.',
      'Require change',
      async () => {
        setBusy(true);
        try {
          const res = await staffApi.requirePasswordChange(staff.id);
          if (!res.success) throw new Error(res.message || 'Could not update.');
          showSuccess('Done', res.message ?? 'They must change password on next sign-in.');
        } catch (err) {
          showError('Failed', err instanceof Error ? err.message : 'Could not require password change.');
        } finally {
          setBusy(false);
        }
      },
      true,
    );
  };

  return (
    <>
      <StaffFieldSection
        title="Position & organisation"
        rows={[
          { label: 'Department', value: staff.departmentName },
          { label: 'Job title', value: staff.jobTitle },
          { label: 'Category', value: staff.staffCategory },
          { label: 'System role', value: staff.systemRole },
          { label: 'Supervisor', value: staff.supervisorName },
          { label: 'Max lessons / week', value: staff.maxLessonsPerWeek?.toString() ?? null },
        ]}
      />

      <StaffFieldSection
        title="Contract & tenure"
        rows={[
          { label: 'Employment status', value: capitalizeStatus(staff.employmentStatus ?? '') },
          { label: 'Employment type', value: staff.employmentType },
          { label: 'Hire date', value: staff.hireDate },
          { label: 'Termination date', value: staff.terminationDate },
          { label: 'Contract start', value: staff.contractStartDate },
          { label: 'Contract end', value: staff.contractEndDate },
        ]}
      />

      <StaffFieldSection
        title="Identity & contact"
        rows={[
          { label: 'ID number', value: staff.idNumber },
          { label: 'Date of birth', value: staff.dateOfBirth },
          { label: 'Gender', value: staff.gender },
          { label: 'Marital status', value: staff.maritalStatus },
          { label: 'Work email', value: staff.email },
          { label: 'Personal email', value: staff.personalEmail },
          { label: 'Phone', value: staff.phone },
          { label: 'Address', value: staff.residentialAddress },
        ]}
      />

      <View style={{ marginBottom: spacing.md }}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
          Login credentials
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
          Reset or share portal/app login password. Device app PIN is managed by the user in Settings (not stored on the server).
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <Button
            label="Reset to ID number"
            variant="secondary"
            loading={busy}
            onPress={() => resetAndShare('id_number')}
          />
          <Button
            label="Reset random password"
            variant="secondary"
            loading={busy}
            onPress={() => resetAndShare('random')}
          />
          <Button label="Resend credentials" variant="ghost" loading={busy} onPress={resend} />
          <Button
            label="Require change on next sign-in"
            variant="ghost"
            loading={busy}
            onPress={forceChangeOnNextLogin}
          />
        </View>
      </View>

      <StaffFieldSection
        title="Emergency contact"
        rows={[
          { label: 'Name', value: staff.emergencyContact.name },
          { label: 'Relationship', value: staff.emergencyContact.relationship },
          { label: 'Phone', value: staff.emergencyContact.phone },
        ]}
      />

      {canViewFinance ? (
        <StaffFieldSection
          title="Payroll & statutory"
          rows={[
            { label: 'Configured basic salary', value: staff.basicSalary?.toLocaleString('en-KE') },
            { label: 'Bank', value: staff.bankName },
            { label: 'Branch', value: staff.bankBranch },
            { label: 'Account', value: staff.bankAccount },
            { label: 'KRA PIN', value: staff.kraPin },
            { label: 'NSSF', value: staff.nssf },
            { label: 'NHIF', value: staff.nhif },
            { label: 'Statutory exemptions', value: exemptions },
          ]}
        />
      ) : null}
    </>
  );
};
