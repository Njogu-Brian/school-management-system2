import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface MarksMatrixRowProps {
  studentName: string;
  admissionNumber: string;
  cells: Array<{ examName: string; score: string }>;
}

export const MarksMatrixRow: React.FC<MarksMatrixRowProps> = ({
  studentName,
  admissionNumber,
  cells,
}) => {
  const { palette, spacing, fontSizes, radius } = useTheme();

  return (
    <View
      style={[
        styles.wrap,
        {
          borderBottomColor: palette.border,
          paddingVertical: spacing.sm,
        },
      ]}
    >
      <View style={{ marginBottom: spacing.xs }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '700' }} numberOfLines={1}>
          {studentName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{admissionNumber}</Text>
      </View>
      <ScrollView horizontal showsHorizontalScrollIndicator={false}>
        <View style={{ flexDirection: 'row', gap: spacing.xs }}>
          {cells.map((cell) => (
            <View
              key={cell.examName}
              style={[
                styles.cell,
                {
                  backgroundColor: palette.accent,
                  borderRadius: radius.sm,
                  padding: spacing.xs,
                  minWidth: 72,
                },
              ]}
            >
              <Text style={{ color: palette.textSecondary, fontSize: 9, fontWeight: '600' }} numberOfLines={1}>
                {cell.examName}
              </Text>
              <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, fontWeight: '700', marginTop: 2 }}>
                {cell.score}
              </Text>
            </View>
          ))}
        </View>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { borderBottomWidth: StyleSheet.hairlineWidth },
  cell: { alignItems: 'center' },
});
