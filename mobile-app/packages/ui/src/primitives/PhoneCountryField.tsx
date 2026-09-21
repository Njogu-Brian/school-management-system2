import {
  COUNTRY_DIAL_CODES,
  DEFAULT_COUNTRY_DIAL_CODE,
  countryDialLabel,
} from '@erp/core';
import { Ionicons } from '@expo/vector-icons';
import React, { useMemo, useState } from 'react';
import {
  FlatList,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { TextField } from './TextField';
import { useTheme } from '../theme/ThemeContext';

export interface PhoneCountryFieldProps {
  label: string;
  countryCode: string;
  nationalNumber: string;
  onCountryCodeChange: (code: string) => void;
  onNationalNumberChange: (value: string) => void;
  placeholder?: string;
}

export const PhoneCountryField: React.FC<PhoneCountryFieldProps> = ({
  label,
  countryCode,
  nationalNumber,
  onCountryCodeChange,
  onNationalNumberChange,
  placeholder = 'Local digits only',
}) => {
  const { palette, colors, spacing, typography, radius, opacity } = useTheme();
  const insets = useSafeAreaInsets();
  const { height } = useWindowDimensions();
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const code = countryCode || DEFAULT_COUNTRY_DIAL_CODE;

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return COUNTRY_DIAL_CODES;
    return COUNTRY_DIAL_CODES.filter(
      (row) =>
        row.name.toLowerCase().includes(q) ||
        row.code.includes(q.replace(/\s/g, '')) ||
        row.label.toLowerCase().includes(q),
    );
  }, [query]);

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
        {label}
      </Text>
      <View style={{ flexDirection: 'row', gap: spacing.sm, alignItems: 'flex-start' }}>
        <Pressable
          onPress={() => {
            setQuery('');
            setOpen(true);
          }}
          accessibilityRole="button"
          accessibilityLabel={`Country code ${countryDialLabel(code)}`}
          style={{
            minWidth: 96,
            borderWidth: StyleSheet.hairlineWidth,
            borderColor: palette.border,
            borderRadius: radius.md,
            backgroundColor: palette.surface,
            paddingHorizontal: spacing.sm,
            paddingVertical: spacing.sm,
            flexDirection: 'row',
            alignItems: 'center',
            gap: 4,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }} numberOfLines={1}>
            {code}
          </Text>
          <Ionicons name="chevron-down" size={16} color={palette.textMuted} />
        </Pressable>
        <View style={{ flex: 1 }}>
          <TextField
            label=""
            value={nationalNumber}
            onChangeText={onNationalNumberChange}
            keyboardType="phone-pad"
            placeholder={placeholder}
          />
        </View>
      </View>

      <Modal visible={open} transparent animationType="slide" onRequestClose={() => setOpen(false)}>
        <View style={styles.overlay}>
          <Pressable
            style={[styles.backdrop, { backgroundColor: `rgba(0,0,0,${opacity.scrim})` }]}
            onPress={() => setOpen(false)}
          />
          <View
            style={{
              backgroundColor: palette.surfaceRaised,
              borderTopLeftRadius: radius.sheet,
              borderTopRightRadius: radius.sheet,
              maxHeight: height * 0.8,
              paddingBottom: insets.bottom + spacing.md,
            }}
          >
            <View style={{ padding: spacing.md, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: palette.borderSubtle }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.title.fontSize }}>
                Country code
              </Text>
              <TextInput
                value={query}
                onChangeText={setQuery}
                placeholder="Search country or code…"
                placeholderTextColor={palette.textMuted}
                autoFocus
                style={{
                  marginTop: spacing.sm,
                  borderWidth: StyleSheet.hairlineWidth,
                  borderColor: palette.border,
                  borderRadius: radius.md,
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.sm,
                  color: palette.textPrimary,
                }}
              />
            </View>
            <FlatList
              data={filtered}
              keyExtractor={(item) => `${item.name}-${item.code}`}
              keyboardShouldPersistTaps="handled"
              renderItem={({ item }) => {
                const active = item.code === code && item.name === COUNTRY_DIAL_CODES.find((r) => r.code === code)?.name
                  ? item.label === countryDialLabel(code)
                  : item.code === code;
                return (
                  <Pressable
                    onPress={() => {
                      onCountryCodeChange(item.code);
                      setOpen(false);
                    }}
                    style={{
                      paddingHorizontal: spacing.md,
                      paddingVertical: spacing.md,
                      backgroundColor: item.code === code ? colors.primaryMuted : 'transparent',
                    }}
                  >
                    <Text style={{ color: item.code === code ? colors.primary : palette.textPrimary, fontWeight: item.code === code ? '700' : '400' }}>
                      {item.label}
                    </Text>
                  </Pressable>
                );
              }}
              ListEmptyComponent={
                <Text style={{ color: palette.textMuted, padding: spacing.md, textAlign: 'center' }}>No matches</Text>
              }
            />
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  overlay: { flex: 1, justifyContent: 'flex-end' },
  backdrop: { ...StyleSheet.absoluteFill },
});
