import type { StudentDetail } from '@erp/core';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

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
  const { palette, fontSizes, spacing } = useTheme();
  if (!name && !phone) return null;
  return (
    <View style={[styles.row, { borderBottomColor: palette.border, paddingVertical: spacing.sm }]}>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
        {label}
      </Text>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, marginTop: 2 }}>
        {name ?? '—'}
      </Text>
      {phone ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{phone}</Text>
      ) : null}
      {email ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{email}</Text>
      ) : null}
    </View>
  );
}

export const FamilyTab: React.FC<FamilyTabProps> = ({ student }) => {
  const { palette, fontSizes, spacing } = useTheme();
  const { parent, guardians, emergencyContact } = student;

  return (
    <View>
      <Text style={[styles.section, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
        Parents
      </Text>
      <ContactRow label="Father" name={parent?.fatherName} phone={parent?.fatherPhone} email={parent?.fatherEmail} />
      <ContactRow label="Mother" name={parent?.motherName} phone={parent?.motherPhone} email={parent?.motherEmail} />
      <ContactRow
        label="Guardian"
        name={parent?.guardianName}
        phone={parent?.guardianPhone}
        email={parent?.guardianEmail}
      />

      <Text
        style={[
          styles.section,
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.lg },
        ]}
      >
        Contacts
      </Text>
      {guardians.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>No guardian records.</Text>
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
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.lg },
        ]}
      >
        Emergency
      </Text>
      <ContactRow
        label="Emergency contact"
        name={emergencyContact.name}
        phone={emergencyContact.phone}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  section: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase', marginBottom: 4 },
  row: { borderBottomWidth: StyleSheet.hairlineWidth },
});
