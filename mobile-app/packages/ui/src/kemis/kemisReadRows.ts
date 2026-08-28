import type { KemisLearnerValues, KemisOptions } from '@erp/core';
import { formatLearnerInterests, formatOrphanStatus } from '@erp/core';

export interface KemisLearnerReadRowsOptions {
  options?: KemisOptions;
}

export function kemisLearnerReadRows(
  values: Partial<KemisLearnerValues> | null | undefined,
  opts?: KemisLearnerReadRowsOptions,
): Array<{ label: string; value: string | null }> {
  if (!values) return [];
  return [
    { label: 'Nationality', value: values.nationality ?? null },
    { label: 'County of birth', value: values.county_of_birth ?? null },
    { label: 'Sub-county of birth', value: values.sub_county_of_birth ?? null },
    { label: 'Location of birth', value: values.location_of_birth ?? null },
    { label: 'Birth certificate entry no.', value: values.birth_certificate_entry_no ?? null },
    { label: 'Medical condition', value: values.medical_condition ?? null },
    { label: 'Religion', value: values.religion ?? null },
    {
      label: 'Orphan status',
      value: formatOrphanStatus(values.orphan_status, opts?.options),
    },
    {
      label: 'Special needs',
      value: values.has_special_needs == null ? null : values.has_special_needs ? 'Yes' : 'No',
    },
    { label: 'Disability type', value: values.disability_type ?? null },
    {
      label: 'Learner interests',
      value: formatLearnerInterests(values.learner_interests, values.learner_interests_other),
    },
  ];
}
