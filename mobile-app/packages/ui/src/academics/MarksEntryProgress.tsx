import React, { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface MarksEntryProgressProps {
  /** Cells / students that already have a score. */
  entered: number;
  /** Total expected cells (students × visible subjects). */
  total: number;
  dirty?: boolean;
  draftSaved?: boolean;
  offline?: boolean;
  /** Extra context line, e.g. "GRADE 6 · OPENER". */
  contextLabel?: string;
}

/** Always-visible progress strip for marks entry. */
export const MarksEntryProgress: React.FC<MarksEntryProgressProps> = ({
  entered,
  total,
  dirty = false,
  draftSaved = false,
  offline = false,
  contextLabel,
}) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const pct = total > 0 ? Math.round((entered / total) * 100) : 0;

  const chips = useMemo(() => {
    const list: { label: string; color: string }[] = [];
    if (offline) list.push({ label: 'Offline', color: colors.warning });
    if (draftSaved) list.push({ label: 'Draft saved', color: colors.primary });
    if (dirty) list.push({ label: 'Unsaved edits', color: colors.warning });
    return list;
  }, [colors.primary, colors.warning, dirty, draftSaved, offline]);

  return (
    <View
      style={[
        styles.wrap,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      {contextLabel ? (
        <Text
          style={{
            color: palette.textPrimary,
            fontWeight: '700',
            fontSize: typography.body.fontSize,
            marginBottom: spacing.xs,
          }}
        >
          {contextLabel}
        </Text>
      ) : null}
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
          Entered {entered} of {total}
        </Text>
        <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>{pct}%</Text>
      </View>
      <View
        style={{
          height: 8,
          borderRadius: 4,
          backgroundColor: palette.borderSubtle ?? palette.border,
          marginTop: spacing.xs,
          overflow: 'hidden',
        }}
      >
        <View
          style={{
            width: `${Math.min(100, Math.max(0, pct))}%`,
            height: '100%',
            backgroundColor: colors.primary,
          }}
        />
      </View>
      {chips.length > 0 ? (
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: spacing.sm }}>
          {chips.map((c) => (
            <View
              key={c.label}
              style={{
                paddingHorizontal: 8,
                paddingVertical: 3,
                borderRadius: radius.full,
                backgroundColor: `${c.color}18`,
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: `${c.color}55`,
              }}
            >
              <Text style={{ color: c.color, fontSize: 11, fontWeight: '700' }}>{c.label}</Text>
            </View>
          ))}
        </View>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { borderWidth: StyleSheet.hairlineWidth },
});
