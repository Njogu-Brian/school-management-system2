export type ParentSlot = 'father' | 'mother' | 'guardian';

export interface KemisOptions {
  nationalities: string[];
  counties: string[];
  religions: string[];
  learner_interests: string[];
  orphan_statuses: Record<string, string>;
  disability_types: string[];
  id_types: string[];
  countries_of_residence: string[];
}

export interface KemisLearnerValues {
  nationality: string;
  county_of_birth: string;
  sub_county_of_birth: string;
  location_of_birth: string;
  birth_certificate_entry_no: string;
  medical_condition: string;
  religion: string;
  religion_other: string;
  orphan_status: string;
  has_special_needs: boolean;
  disability_type: string;
  learner_interests: string[];
  learner_interests_other: string;
}

export interface KemisParentSlotValues {
  first_name: string;
  middle_name: string;
  last_name: string;
  id_type: string;
  id_number: string;
  country_of_residence: string;
  phone: string;
  whatsapp: string;
  email: string;
}

export const emptyKemisLearnerValues = (): KemisLearnerValues => ({
  nationality: 'Kenyan',
  county_of_birth: '',
  sub_county_of_birth: '',
  location_of_birth: '',
  birth_certificate_entry_no: '',
  medical_condition: '',
  religion: '',
  religion_other: '',
  orphan_status: '',
  has_special_needs: false,
  disability_type: '',
  learner_interests: [],
  learner_interests_other: '',
});

export const emptyKemisParentSlotValues = (): KemisParentSlotValues => ({
  first_name: '',
  middle_name: '',
  last_name: '',
  id_type: '',
  id_number: '',
  country_of_residence: '',
  phone: '',
  whatsapp: '',
  email: '',
});

export function formatParentSlotName(slot: KemisParentSlotValues): string {
  return [slot.first_name, slot.middle_name, slot.last_name].filter(Boolean).join(' ').trim();
}

export function resolvedParentSlotName(
  data: Record<string, unknown>,
  slot: ParentSlot,
): string {
  const fromParts = formatParentSlotName({
    first_name: String(data[`${slot}_first_name`] ?? '').trim(),
    middle_name: String(data[`${slot}_middle_name`] ?? '').trim(),
    last_name: String(data[`${slot}_last_name`] ?? '').trim(),
    id_type: '',
    id_number: '',
    country_of_residence: '',
    phone: '',
    whatsapp: '',
    email: '',
  });
  if (fromParts) return fromParts;
  return String(data[`${slot}_name`] ?? '').trim();
}

export function kemisLearnerPayload(values: KemisLearnerValues): Record<string, unknown> {
  return {
    nationality: values.nationality || null,
    county_of_birth: values.county_of_birth || null,
    sub_county_of_birth: values.sub_county_of_birth || null,
    location_of_birth: values.location_of_birth || null,
    birth_certificate_entry_no: values.birth_certificate_entry_no || null,
    medical_condition: values.medical_condition || null,
    religion: values.religion || null,
    religion_other: values.religion === 'Other' ? values.religion_other || null : null,
    orphan_status: values.orphan_status || null,
    has_special_needs: values.has_special_needs,
    disability_type: values.has_special_needs ? values.disability_type || null : null,
    learner_interests: values.learner_interests,
    learner_interests_other: values.learner_interests_other || null,
  };
}

export function kemisParentSlotPayload(
  slot: ParentSlot,
  values: KemisParentSlotValues,
): Record<string, unknown> {
  return {
    [`${slot}_first_name`]: values.first_name || null,
    [`${slot}_middle_name`]: values.middle_name || null,
    [`${slot}_last_name`]: values.last_name || null,
    [`${slot}_id_type`]: values.id_type || null,
    [`${slot}_id_number`]: values.id_number || null,
    [`${slot}_country_of_residence`]: values.country_of_residence || null,
    [`${slot}_phone`]: values.phone || null,
    [`${slot}_whatsapp`]: values.whatsapp || null,
    [`${slot}_email`]: values.email || null,
  };
}

export function kemisLearnerFromApi(raw: Record<string, unknown> | null | undefined): KemisLearnerValues {
  const interests = raw?.learner_interests;
  return {
    nationality: String(raw?.nationality ?? 'Kenyan'),
    county_of_birth: String(raw?.county_of_birth ?? ''),
    sub_county_of_birth: String(raw?.sub_county_of_birth ?? ''),
    location_of_birth: String(raw?.location_of_birth ?? ''),
    birth_certificate_entry_no: String(raw?.birth_certificate_entry_no ?? ''),
    medical_condition: String(raw?.medical_condition ?? ''),
    religion: String(raw?.religion ?? ''),
    religion_other: '',
    orphan_status: String(raw?.orphan_status ?? ''),
    has_special_needs: Boolean(raw?.has_special_needs),
    disability_type: String(raw?.disability_type ?? ''),
    learner_interests: Array.isArray(interests) ? interests.map(String) : [],
    learner_interests_other: '',
  };
}

export function kemisParentSlotFromApi(
  raw: Record<string, unknown> | null | undefined,
  slot: ParentSlot,
): KemisParentSlotValues {
  return {
    first_name: String(raw?.[`${slot}_first_name`] ?? ''),
    middle_name: String(raw?.[`${slot}_middle_name`] ?? ''),
    last_name: String(raw?.[`${slot}_last_name`] ?? ''),
    id_type: String(raw?.[`${slot}_id_type`] ?? ''),
    id_number: String(raw?.[`${slot}_id_number`] ?? ''),
    country_of_residence: String(raw?.[`${slot}_country_of_residence`] ?? ''),
    phone: String(raw?.[`${slot}_phone`] ?? ''),
    whatsapp: String(raw?.[`${slot}_whatsapp`] ?? ''),
    email: String(raw?.[`${slot}_email`] ?? ''),
  };
}

export function formatOrphanStatus(value: string | null | undefined, options?: KemisOptions): string {
  if (!value) return '—';
  return options?.orphan_statuses?.[value] ?? value;
}

export function formatLearnerInterests(
  interests: string[] | null | undefined,
  other?: string | null,
): string {
  const list = [...(interests ?? [])];
  if (other?.trim()) list.push(other.trim());
  return list.length ? list.join(', ') : '—';
}
