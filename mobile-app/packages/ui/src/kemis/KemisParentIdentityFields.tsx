import type { KemisOptions, KemisParentSlotValues, ParentSlot } from '@erp/core';
import React from 'react';
import { Text, View } from 'react-native';
import { TextField } from '../primitives';
import { useTheme } from '../theme';
import { OptionSelectField } from './OptionSelectField';

export interface KemisParentIdentityFieldsProps {
  slot: ParentSlot;
  title: string;
  values: KemisParentSlotValues;
  onChange: (values: KemisParentSlotValues) => void;
  options: KemisOptions;
  showContactFields?: boolean;
  showRelationship?: boolean;
  relationship?: string;
  onRelationshipChange?: (value: string) => void;
}

export const KemisParentIdentityFields: React.FC<KemisParentIdentityFieldsProps> = ({
  title,
  values,
  onChange,
  options,
  showContactFields = true,
  showRelationship = false,
  relationship = '',
  onRelationshipChange,
}) => {
  const { palette, typography, spacing } = useTheme();
  const set = <K extends keyof KemisParentSlotValues>(key: K, value: KemisParentSlotValues[K]) => {
    onChange({ ...values, [key]: value });
  };

  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontWeight: '700',
          fontSize: typography.caption.fontSize,
          textTransform: 'uppercase',
          letterSpacing: 0.4,
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      <TextField label="First name" value={values.first_name} onChangeText={(v) => set('first_name', v)} />
      <TextField label="Middle name" value={values.middle_name} onChangeText={(v) => set('middle_name', v)} />
      <TextField label="Last name" value={values.last_name} onChangeText={(v) => set('last_name', v)} />
      {showRelationship ? (
        <TextField
          label="Relationship"
          value={relationship}
          onChangeText={(v) => onRelationshipChange?.(v)}
          placeholder="e.g. Aunt, Uncle"
        />
      ) : null}
      <OptionSelectField
        label="Type of ID"
        value={values.id_type}
        options={options.id_types}
        onChange={(v) => set('id_type', v)}
      />
      <TextField label="National ID no." value={values.id_number} onChangeText={(v) => set('id_number', v)} />
      <OptionSelectField
        label="Country of residence"
        value={values.country_of_residence}
        options={options.countries_of_residence}
        onChange={(v) => set('country_of_residence', v)}
      />
      {showContactFields ? (
        <>
          <TextField
            label="Phone"
            value={values.phone}
            onChangeText={(v) => set('phone', v)}
            keyboardType="phone-pad"
          />
          <TextField
            label="WhatsApp"
            value={values.whatsapp}
            onChangeText={(v) => set('whatsapp', v)}
            keyboardType="phone-pad"
          />
          <TextField
            label="Email"
            value={values.email}
            onChangeText={(v) => set('email', v)}
            keyboardType="email-address"
            autoCapitalize="none"
          />
        </>
      ) : null}
    </View>
  );
};
