import React, { useMemo, useState } from 'react';
import { Text, View } from 'react-native';
import { FilterChip, FilterChipRow, TextField, useTheme } from '@erp/ui';

const COUNTRY_CODES = [
  { code: '+254', label: 'KE +254' },
  { code: '+256', label: 'UG +256' },
  { code: '+255', label: 'TZ +255' },
  { code: '+1', label: 'US/CA +1' },
  { code: '+44', label: 'UK +44' },
  { code: '+234', label: 'NG +234' },
];

export type InternationalPhoneValue = {
  countryCode: string;
  localNumber: string;
};

export function parseStoredPhone(raw?: string | null): InternationalPhoneValue {
  const digits = String(raw ?? '').replace(/\D+/g, '');
  if (digits.startsWith('254') && digits.length >= 12) {
    return { countryCode: '+254', localNumber: digits.slice(3) };
  }
  if (digits.startsWith('1') && digits.length === 11) {
    return { countryCode: '+1', localNumber: digits.slice(1) };
  }
  if (digits.startsWith('44') && digits.length >= 11) {
    return { countryCode: '+44', localNumber: digits.slice(2) };
  }
  return { countryCode: '+254', localNumber: digits.replace(/^0+/, '') };
}

export function formatInternationalPhone(value: InternationalPhoneValue): string {
  const local = value.localNumber.replace(/\D+/g, '').replace(/^0+/, '');
  const cc = value.countryCode.startsWith('+') ? value.countryCode : `+${value.countryCode}`;
  if (!local) return '';
  return `${cc}${local}`;
}

/** Basic length check for supported country codes (not Kenya-only). */
export function validateInternationalPhone(value: InternationalPhoneValue): string | null {
  const local = value.localNumber.replace(/\D+/g, '').replace(/^0+/, '');
  if (!local) return 'Phone number is required.';
  const rules: Record<string, { min: number; max: number }> = {
    '+254': { min: 9, max: 9 },
    '+256': { min: 9, max: 9 },
    '+255': { min: 9, max: 9 },
    '+1': { min: 10, max: 10 },
    '+44': { min: 9, max: 10 },
    '+234': { min: 10, max: 10 },
  };
  const rule = rules[value.countryCode] ?? { min: 4, max: 15 };
  if (local.length < rule.min || local.length > rule.max) {
    return `Enter ${rule.min === rule.max ? rule.min : `${rule.min}-${rule.max}`} digits for ${value.countryCode}.`;
  }
  return null;
}

export const InternationalPhoneField: React.FC<{
  label: string;
  value: InternationalPhoneValue;
  onChange: (next: InternationalPhoneValue) => void;
  error?: string | null;
  required?: boolean;
}> = ({ label, value, onChange, error, required }) => {
  const { palette, spacing, typography } = useTheme();
  const [showCodes, setShowCodes] = useState(false);
  const displayLabel = useMemo(
    () => `${label}${required ? ' *' : ''}`,
    [label, required],
  );

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <Text style={{ color: palette.textSecondary, marginBottom: spacing.xs, fontSize: typography.caption.fontSize }}>
        {displayLabel}
      </Text>
      <FilterChipRow>
        <FilterChip
          label={COUNTRY_CODES.find((c) => c.code === value.countryCode)?.label ?? value.countryCode}
          active
          onPress={() => setShowCodes((v) => !v)}
        />
      </FilterChipRow>
      {showCodes ? (
        <FilterChipRow label="Country code">
          {COUNTRY_CODES.map((c) => (
            <FilterChip
              key={c.code}
              label={c.label}
              active={value.countryCode === c.code}
              onPress={() => {
                onChange({ ...value, countryCode: c.code });
                setShowCodes(false);
              }}
            />
          ))}
        </FilterChipRow>
      ) : null}
      <TextField
        label="Local number"
        value={value.localNumber}
        onChangeText={(localNumber) => onChange({ ...value, localNumber })}
        keyboardType="phone-pad"
        error={error}
        placeholder="National number without country code"
      />
    </View>
  );
};
