import type { KemisLearnerValues, KemisOptions } from '@erp/core';
import React from 'react';
import { Text, View } from 'react-native';
import { FilterChip, FilterChipRow, TextField } from '../primitives';
import { useTheme } from '../theme/ThemeContext';
import { OptionSelectField } from './OptionSelectField';

export interface KemisLearnerFieldsProps {
  values: KemisLearnerValues;
  onChange: (values: KemisLearnerValues) => void;
  options: KemisOptions;
}

export const KemisLearnerFields: React.FC<KemisLearnerFieldsProps> = ({ values, onChange, options }) => {
  const { palette, typography, spacing } = useTheme();
  const set = <K extends keyof KemisLearnerValues>(key: K, value: KemisLearnerValues[K]) => {
    onChange({ ...values, [key]: value });
  };

  const toggleInterest = (interest: string) => {
    const next = values.learner_interests.includes(interest)
      ? values.learner_interests.filter((i) => i !== interest)
      : [...values.learner_interests, interest];
    set('learner_interests', next);
  };

  const orphanEntries = Object.entries(options.orphan_statuses);

  return (
    <View>
      <OptionSelectField
        label="Nationality / country of birth"
        value={values.nationality}
        options={options.nationalities}
        onChange={(v) => set('nationality', v)}
        required
      />
      <OptionSelectField
        label="County of birth"
        value={values.county_of_birth}
        options={options.counties}
        onChange={(v) => set('county_of_birth', v)}
        required
        searchable
      />
      <TextField
        label="Sub-county of birth"
        value={values.sub_county_of_birth}
        onChangeText={(v) => set('sub_county_of_birth', v)}
      />
      <TextField
        label="Location of birth"
        value={values.location_of_birth}
        onChangeText={(v) => set('location_of_birth', v)}
      />
      <TextField
        label="Birth certificate entry no."
        value={values.birth_certificate_entry_no}
        onChangeText={(v) => set('birth_certificate_entry_no', v)}
      />
      <TextField
        label="Medical condition"
        value={values.medical_condition}
        onChangeText={(v) => set('medical_condition', v)}
        placeholder="None if not applicable"
      />
      <OptionSelectField
        label="Religion"
        value={values.religion}
        options={options.religions}
        onChange={(v) => set('religion', v)}
        required
      />
      {values.religion === 'Other' ? (
        <TextField
          label="Specify religion"
          value={values.religion_other}
          onChangeText={(v) => set('religion_other', v)}
        />
      ) : null}
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.xs }}>
        Orphan status *
      </Text>
      <FilterChipRow label="">
        {orphanEntries.map(([value, label]) => (
          <FilterChip
            key={value}
            label={label}
            active={values.orphan_status === value}
            onPress={() => set('orphan_status', value)}
          />
        ))}
      </FilterChipRow>
      <FilterChipRow label="Special needs / disability">
        <FilterChip label="No" active={!values.has_special_needs} onPress={() => set('has_special_needs', false)} />
        <FilterChip label="Yes" active={values.has_special_needs} onPress={() => set('has_special_needs', true)} />
      </FilterChipRow>
      {values.has_special_needs ? (
        <OptionSelectField
          label="Disability type"
          value={values.disability_type}
          options={options.disability_types}
          onChange={(v) => set('disability_type', v)}
          required
        />
      ) : null}
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.sm, marginBottom: spacing.xs }}>
        Learner interests *
      </Text>
      <FilterChipRow label="">
        {options.learner_interests.map((interest) => (
          <FilterChip
            key={interest}
            label={interest}
            active={values.learner_interests.includes(interest)}
            onPress={() => toggleInterest(interest)}
          />
        ))}
      </FilterChipRow>
      <TextField
        label="Other interest"
        value={values.learner_interests_other}
        onChangeText={(v) => set('learner_interests_other', v)}
        placeholder="Optional — counts if none selected above"
      />
    </View>
  );
};
