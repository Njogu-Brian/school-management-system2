import { studentsApi, type StudentDetail } from '@erp/core';
import { Button, EmptyState, Soft3DIcon, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useCallback, useEffect, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { openEmail, openPhoneActions } from '../../../../utils/contactActions';
import { confirmAction, showError, showSuccess } from '../../../shared/utils/feedback';

export interface FamilyTabProps {
  student: StudentDetail;
}

type ParentAccount = {
  user_id: number;
  name: string;
  login: string | null;
  phone: string | null;
  must_change_password: boolean;
};

function ContactCard({
  label,
  name,
  phone,
  email,
}: {
  label: string;
  name?: string | null;
  phone?: string | null;
  email?: string | null;
}) {
  const { palette, typography, spacing, radius, elevation } = useTheme();
  if (!name && !phone && !email) return null;
  return (
    <View
      style={[
        styles.card,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <View style={styles.cardHeader}>
        <Soft3DIcon name="person-outline" size={36} />
        <View style={{ marginLeft: spacing.sm, flex: 1 }}>
          <Text
            style={{
              color: palette.textMuted,
              fontSize: typography.caption.fontSize,
              fontWeight: '700',
              textTransform: 'uppercase',
              letterSpacing: 0.4,
            }}
          >
            {label}
          </Text>
          <Text
            style={{
              color: palette.textMain,
              fontSize: typography.bodyLarge.fontSize,
              fontWeight: '700',
              marginTop: 2,
            }}
          >
            {name ?? '—'}
          </Text>
        </View>
      </View>
      {phone ? (
        <Pressable
          onPress={() => void openPhoneActions(phone, name ?? label)}
          style={[styles.actionRow, { marginTop: spacing.sm }]}
        >
          <Ionicons name="call-outline" size={16} color={palette.primary} />
          <Text style={{ color: palette.primary, marginLeft: 8, fontWeight: '600' }}>{phone}</Text>
        </Pressable>
      ) : null}
      {email ? (
        <Pressable onPress={() => void openEmail(email)} style={[styles.actionRow, { marginTop: spacing.xs }]}>
          <Ionicons name="mail-outline" size={16} color={palette.primary} />
          <Text style={{ color: palette.primary, marginLeft: 8, fontWeight: '600' }}>{email}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

function SectionTitle({ title }: { title: string }) {
  const { palette, typography, spacing } = useTheme();
  return (
    <Text
      style={{
        color: palette.textSub,
        fontSize: typography.overline.fontSize,
        letterSpacing: typography.overline.letterSpacing,
        fontWeight: '700',
        textTransform: 'uppercase',
        marginBottom: spacing.sm,
        marginTop: spacing.md,
      }}
    >
      {title}
    </Text>
  );
}

export const FamilyTab: React.FC<FamilyTabProps> = ({ student }) => {
  const { spacing, palette, typography, colors } = useTheme();
  const { parent, guardians, emergencyContact } = student;
  const [accounts, setAccounts] = useState<ParentAccount[]>([]);
  const [busy, setBusy] = useState(false);
  const [loaded, setLoaded] = useState(false);

  const loadAccounts = useCallback(async () => {
    try {
      const res = await studentsApi.parentCredentials(student.id);
      if (res.success && res.data) {
        setAccounts(res.data.accounts ?? []);
      }
    } catch {
      /* non-blocking */
    } finally {
      setLoaded(true);
    }
  }, [student.id]);

  useEffect(() => {
    void loadAccounts();
  }, [loadAccounts]);

  const resetAccount = (account?: ParentAccount) => {
    confirmAction(
      'Reset parent login',
      account
        ? `Generate a temporary password for ${account.name} (${account.login ?? 'no email'}) and show it for sharing?`
        : 'Generate a temporary password for the linked parent account and show it for sharing?',
      'Reset & share',
      async () => {
        setBusy(true);
        try {
          const res = await studentsApi.resetParentCredentials(student.id, {
            user_id: account?.user_id,
            password_option: 'random',
            share: true,
          });
          if (!res.success || !res.data) throw new Error(res.message || 'Reset failed.');
          showSuccess(
            'Password reset',
            `Login: ${res.data.login ?? '—'}\nTemporary password: ${res.data.temporary_password}\nThey must change it on next sign-in. App PIN stays on their device — reset in Settings.`,
          );
          void loadAccounts();
        } catch (err) {
          showError('Reset failed', err instanceof Error ? err.message : 'Could not reset password.');
        } finally {
          setBusy(false);
        }
      },
      true,
    );
  };

  const hasParent =
    !!(
      parent?.fatherName ||
      parent?.fatherPhone ||
      parent?.fatherEmail ||
      parent?.motherName ||
      parent?.motherPhone ||
      parent?.motherEmail ||
      parent?.guardianName ||
      parent?.guardianPhone ||
      parent?.guardianEmail
    );
  const hasEmergency = !!(emergencyContact.name || emergencyContact.phone);
  const isEmpty = !hasParent && guardians.length === 0 && !hasEmergency;

  if (isEmpty) {
    return (
      <EmptyState
        title="No family records"
        message="No parent, guardian, or emergency contacts are on file for this student."
        icon="people-outline"
      />
    );
  }

  return (
    <View style={{ paddingBottom: spacing.md }}>
      <SectionTitle title="Parents" />
      {!hasParent ? (
        <EmptyState
          title="No parents listed"
          message="Parent details have not been added yet."
          icon="person-outline"
        />
      ) : (
        <>
          <ContactCard
            label="Father"
            name={parent?.fatherName}
            phone={parent?.fatherPhone}
            email={parent?.fatherEmail}
          />
          <ContactCard
            label="Mother"
            name={parent?.motherName}
            phone={parent?.motherPhone}
            email={parent?.motherEmail}
          />
          <ContactCard
            label="Guardian"
            name={parent?.guardianName}
            phone={parent?.guardianPhone}
            email={parent?.guardianEmail}
          />
        </>
      )}

      <SectionTitle title="Portal login" />
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
        Reset or share parent app/portal password. Device PIN is managed by the parent in Settings (not on the server).
      </Text>
      {!loaded ? (
        <Text style={{ color: palette.textMuted, marginBottom: spacing.sm }}>Checking linked accounts…</Text>
      ) : accounts.length === 0 ? (
        <EmptyState
          title="No parent account"
          message="No linked parent login yet. Parents create one via claim/OTP, or enrollments may create one without sharing the password."
          icon="lock-closed-outline"
        />
      ) : (
        accounts.map((a) => (
          <View
            key={a.user_id}
            style={{
              marginBottom: spacing.sm,
              padding: spacing.md,
              borderWidth: StyleSheet.hairlineWidth,
              borderColor: palette.borderSubtle,
              borderRadius: 12,
              backgroundColor: palette.surfaceRaised,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{a.name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {a.login ?? 'No email'}
              {a.phone ? ` · ${a.phone}` : ''}
            </Text>
            <Button
              label="Reset & share password"
              variant="secondary"
              loading={busy}
              onPress={() => resetAccount(a)}
              style={{ marginTop: spacing.sm }}
            />
          </View>
        ))
      )}
      {loaded && accounts.length === 0 ? (
        <Text style={{ color: colors.warning, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
          Tip: after the parent claims access, return here to reset if they are locked out.
        </Text>
      ) : null}

      <SectionTitle title="Contacts" />
      {guardians.length === 0 ? (
        <EmptyState
          title="No guardians"
          message="No guardian records are linked to this student."
          icon="people-outline"
        />
      ) : (
        guardians.map((g) => (
          <ContactCard
            key={g.id}
            label={`${g.relationship}${g.isPrimary ? ' · primary' : ''}`}
            name={g.name}
            phone={g.phone}
            email={g.email}
          />
        ))
      )}

      <SectionTitle title="Emergency" />
      {!hasEmergency ? (
        <EmptyState
          title="No emergency contact"
          message="Add an emergency contact on the student profile."
          icon="call-outline"
        />
      ) : (
        <ContactCard
          label="Emergency contact"
          name={emergencyContact.name}
          phone={emergencyContact.phone}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  cardHeader: { flexDirection: 'row', alignItems: 'center' },
  actionRow: { flexDirection: 'row', alignItems: 'center' },
});
