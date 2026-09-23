import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface MarksScoreSlotProps {
  /** Subject or exam label shown above the inputs. */
  title: string;
  /** Secondary line, e.g. exam name. */
  subtitle?: string;
  minMarks: number;
  maxMarks: number;
  marks: string;
  remarks: string;
  onChangeMarks: (value: string) => void;
  onChangeRemarks: (value: string) => void;
  /** Compact single-line score when stacking many subjects. */
  compact?: boolean;
}

function statusOf(marks: string, min: number, max: number): {
  label: string;
  tone: 'empty' | 'ok' | 'warn' | 'bad';
} {
  const trimmed = marks.trim();
  if (!trimmed) return { label: 'Empty', tone: 'empty' };
  const n = Number(trimmed);
  if (Number.isNaN(n)) return { label: 'Invalid', tone: 'bad' };
  if (n < min || n > max) return { label: `Out of range (${min}–${max})`, tone: 'warn' };
  return { label: 'Entered', tone: 'ok' };
}

/** Full-width score + remarks for one exam/subject — nothing hidden. */
export const MarksScoreSlot: React.FC<MarksScoreSlotProps> = ({
  title,
  subtitle,
  minMarks,
  maxMarks,
  marks,
  remarks,
  onChangeMarks,
  onChangeRemarks,
  compact = false,
}) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const status = useMemo(() => statusOf(marks, minMarks, maxMarks), [marks, maxMarks, minMarks]);
  const pct = useMemo(() => {
    const n = Number(marks);
    if (!marks.trim() || Number.isNaN(n) || maxMarks <= 0) return null;
    return Math.round((n / maxMarks) * 100);
  }, [marks, maxMarks]);

  const toneColor =
    status.tone === 'ok'
      ? colors.success
      : status.tone === 'warn'
        ? colors.warning
        : status.tone === 'bad'
          ? colors.error
          : palette.textMuted;

  return (
    <View
      style={[
        styles.slot,
        {
          borderColor: palette.border,
          backgroundColor: palette.surfaceRaised ?? palette.surface,
          borderRadius: radius.md,
          padding: spacing.sm,
          marginBottom: compact ? spacing.xs : spacing.sm,
        },
      ]}
    >
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 8 }}>
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
            {title}
          </Text>
          {subtitle ? (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
              {subtitle}
            </Text>
          ) : null}
          <Text style={{ color: palette.textMuted, fontSize: 11, marginTop: 2 }}>
            Range {minMarks}–{maxMarks}
          </Text>
        </View>
        <View
          style={{
            paddingHorizontal: 8,
            paddingVertical: 3,
            borderRadius: radius.full,
            backgroundColor: `${toneColor}18`,
          }}
        >
          <Text style={{ color: toneColor, fontSize: 11, fontWeight: '700' }}>{status.label}</Text>
        </View>
      </View>

      <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.sm }}>
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textMuted, fontSize: 11, fontWeight: '600', marginBottom: 4 }}>
            SCORE
          </Text>
          <TextInput
            value={marks}
            onChangeText={(t) => onChangeMarks(t.replace(/[^\d.]/g, ''))}
            keyboardType="decimal-pad"
            placeholder="—"
            placeholderTextColor={palette.textMuted}
            style={{
              borderWidth: 1,
              borderColor: status.tone === 'bad' || status.tone === 'warn' ? toneColor : palette.border,
              borderRadius: radius.control,
              backgroundColor: palette.surface,
              color: palette.textPrimary,
              fontSize: 22,
              fontWeight: '800',
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.sm,
              minHeight: 48,
            }}
          />
        </View>
        <View style={{ alignItems: 'flex-end', minWidth: 64 }}>
          <Text style={{ color: palette.textMuted, fontSize: 11, fontWeight: '600' }}>OF {maxMarks}</Text>
          <Text style={{ color: palette.textPrimary, fontSize: 20, fontWeight: '800', marginTop: 6 }}>
            {pct != null ? `${pct}%` : '—'}
          </Text>
        </View>
      </View>

      <Text style={{ color: palette.textMuted, fontSize: 11, fontWeight: '600', marginTop: spacing.sm, marginBottom: 4 }}>
        REMARKS
      </Text>
      <TextInput
        value={remarks}
        onChangeText={onChangeRemarks}
        placeholder="Optional notes (always visible)"
        placeholderTextColor={palette.textMuted}
        style={{
          borderWidth: 1,
          borderColor: palette.border,
          borderRadius: radius.control,
          backgroundColor: palette.surface,
          color: palette.textPrimary,
          fontSize: typography.body.fontSize,
          paddingHorizontal: spacing.md,
          paddingVertical: spacing.sm,
          minHeight: 40,
        }}
      />

      <Pressable
        onPress={() => {
          onChangeMarks('');
          onChangeRemarks(remarks.trim() ? remarks : 'ABSENT');
        }}
        style={{ marginTop: spacing.xs, alignSelf: 'flex-start' }}
        hitSlop={8}
      >
        <Text style={{ color: colors.primary, fontWeight: '700', fontSize: typography.caption.fontSize }}>
          Mark absent
        </Text>
      </Pressable>
    </View>
  );
};

const styles = StyleSheet.create({
  slot: { borderWidth: StyleSheet.hairlineWidth },
});
