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
import { useTheme } from '../theme/ThemeContext';

export interface OptionSelectFieldProps {
  label: string;
  value: string;
  options: string[];
  onChange: (value: string) => void;
  placeholder?: string;
  required?: boolean;
  searchable?: boolean;
}

export const OptionSelectField: React.FC<OptionSelectFieldProps> = ({
  label,
  value,
  options,
  onChange,
  placeholder = 'Select',
  required,
  searchable = false,
}) => {
  const { palette, colors, spacing, typography, radius, opacity } = useTheme();
  const insets = useSafeAreaInsets();
  const { height } = useWindowDimensions();
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return options;
    return options.filter((o) => o.toLowerCase().includes(q));
  }, [options, query]);

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
        {label}
        {required ? ' *' : ''}
      </Text>
      <Pressable
        onPress={() => {
          setQuery('');
          setOpen(true);
        }}
        style={{
          borderWidth: StyleSheet.hairlineWidth,
          borderColor: palette.border,
          borderRadius: radius.md,
          backgroundColor: palette.surface,
          paddingHorizontal: spacing.md,
          paddingVertical: spacing.sm,
          flexDirection: 'row',
          alignItems: 'center',
        }}
      >
        <Text
          style={{
            flex: 1,
            color: value ? palette.textPrimary : palette.textMuted,
            fontSize: typography.body.fontSize,
          }}
          numberOfLines={1}
        >
          {value || placeholder}
        </Text>
        <Ionicons name="chevron-down" size={18} color={palette.textMuted} />
      </Pressable>

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
              maxHeight: height * 0.75,
              paddingBottom: insets.bottom + spacing.md,
            }}
          >
            <View style={{ padding: spacing.md, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: palette.borderSubtle }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.title.fontSize }}>
                {label}
              </Text>
              {searchable ? (
                <TextInput
                  value={query}
                  onChangeText={setQuery}
                  placeholder="Search…"
                  placeholderTextColor={palette.textMuted}
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
              ) : null}
            </View>
            <FlatList
              data={filtered}
              keyExtractor={(item) => item}
              keyboardShouldPersistTaps="handled"
              renderItem={({ item }) => {
                const active = item === value;
                return (
                  <Pressable
                    onPress={() => {
                      onChange(item);
                      setOpen(false);
                    }}
                    style={{
                      paddingHorizontal: spacing.md,
                      paddingVertical: spacing.md,
                      backgroundColor: active ? colors.primaryMuted : 'transparent',
                    }}
                  >
                    <Text style={{ color: active ? colors.primary : palette.textPrimary, fontWeight: active ? '700' : '400' }}>
                      {item}
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
  backdrop: { ...StyleSheet.absoluteFillObject },
});
