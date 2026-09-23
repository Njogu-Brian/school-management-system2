import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { MarksScoreSlot, type MarksScoreSlotProps } from './MarksScoreSlot';

export interface MarksStudentCardSlot extends Omit<MarksScoreSlotProps, 'compact'> {
  keyId: string;
}

export interface MarksStudentCardProps {
  index: number;
  fullName: string;
  admissionNumber?: string | null;
  slots: MarksStudentCardSlot[];
}

/** Student identity + one or more full-width subject score slots. */
export const MarksStudentCard: React.FC<MarksStudentCardProps> = ({
  index,
  fullName,
  admissionNumber,
  slots,
}) => {
  const { palette, spacing, typography, radius } = useTheme();
  const multi = slots.length > 1;

  return (
    <View
      style={[
        styles.card,
        {
          borderColor: palette.border,
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          padding: spacing.md,
          marginBottom: spacing.md,
        },
      ]}
    >
      <View style={{ marginBottom: spacing.sm }}>
        <Text style={{ color: palette.textPrimary, fontWeight: '800', fontSize: typography.bodyLarge.fontSize }}>
          {index}. {fullName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
          Admission {admissionNumber?.trim() || '—'}
          {multi ? ` · ${slots.length} subjects` : ''}
        </Text>
      </View>
      {slots.map((slot) => {
        const { keyId, ...rest } = slot;
        return <MarksScoreSlot key={keyId} {...rest} compact={multi} />;
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
});
