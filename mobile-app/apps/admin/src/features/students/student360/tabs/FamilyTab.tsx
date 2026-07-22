import type { StudentDetail } from '@erp/core';
import { EmptyState, Soft3DIcon, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { openEmail, openPhoneActions } from '../../../../utils/contactActions';

export interface FamilyTabProps {
  student: StudentDetail;
}

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
  const { spacing } = useTheme();
  const { parent, guardians, emergencyContact } = student;

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
