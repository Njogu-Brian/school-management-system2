import type { StudentDetail } from '@erp/core';
import { EmptyState, useTheme } from '@erp/ui';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { openEmail, openPhoneActions } from '../../../../utils/contactActions';

export interface FamilyTabProps {
  student: StudentDetail;
}

function ContactRow({
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
  const { palette, typography, spacing } = useTheme();
  if (!name && !phone && !email) return null;
  return (
    <View style={[styles.row, { borderBottomColor: palette.border, paddingVertical: spacing.sm }]}>
      <Text
        style={{
          color: palette.textSub,
          fontSize: typography.caption.fontSize,
          fontWeight: '600',
        }}
      >
        {label}
      </Text>
      <Text
        style={{
          color: palette.textMain,
          fontSize: typography.body.fontSize,
          marginTop: spacing.xs,
        }}
      >
        {name ?? '—'}
      </Text>
      {phone ? (
        <Pressable onPress={() => void openPhoneActions(phone, name ?? label)}>
          <Text
            style={{
              color: palette.textMain,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
              textDecorationLine: 'underline',
            }}
          >
            {phone}
          </Text>
        </Pressable>
      ) : null}
      {email ? (
        <Pressable onPress={() => void openEmail(email)}>
          <Text
            style={{
              color: palette.textMain,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
              textDecorationLine: 'underline',
            }}
          >
            {email}
          </Text>
        </Pressable>
      ) : null}
    </View>
  );
}

export const FamilyTab: React.FC<FamilyTabProps> = ({ student }) => {
  const { palette, typography, spacing } = useTheme();
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
    <View>
      <Text
        style={[
          styles.section,
          {
            color: palette.textSub,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginBottom: spacing.xs,
          },
        ]}
      >
        Parents
      </Text>
      {!hasParent ? (
        <EmptyState
          title="No parents listed"
          message="Parent details have not been added yet."
          icon="person-outline"
        />
      ) : (
        <>
          <ContactRow
            label="Father"
            name={parent?.fatherName}
            phone={parent?.fatherPhone}
            email={parent?.fatherEmail}
          />
          <ContactRow
            label="Mother"
            name={parent?.motherName}
            phone={parent?.motherPhone}
            email={parent?.motherEmail}
          />
          <ContactRow
            label="Guardian"
            name={parent?.guardianName}
            phone={parent?.guardianPhone}
            email={parent?.guardianEmail}
          />
        </>
      )}

      <Text
        style={[
          styles.section,
          {
            color: palette.textSub,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginTop: spacing.lg,
            marginBottom: spacing.xs,
          },
        ]}
      >
        Contacts
      </Text>
      {guardians.length === 0 ? (
        <EmptyState
          title="No guardians"
          message="No guardian records are linked to this student."
          icon="people-outline"
        />
      ) : (
        guardians.map((g) => (
          <ContactRow
            key={g.id}
            label={`${g.relationship}${g.isPrimary ? ' (primary)' : ''}`}
            name={g.name}
            phone={g.phone}
            email={g.email}
          />
        ))
      )}

      <Text
        style={[
          styles.section,
          {
            color: palette.textSub,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginTop: spacing.lg,
            marginBottom: spacing.xs,
          },
        ]}
      >
        Emergency
      </Text>
      {!hasEmergency ? (
        <EmptyState
          title="No emergency contact"
          message="Add an emergency contact on the student profile."
          icon="call-outline"
        />
      ) : (
        <ContactRow
          label="Emergency contact"
          name={emergencyContact.name}
          phone={emergencyContact.phone}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  section: { fontWeight: '700', textTransform: 'uppercase' },
  row: { borderBottomWidth: StyleSheet.hairlineWidth },
});
