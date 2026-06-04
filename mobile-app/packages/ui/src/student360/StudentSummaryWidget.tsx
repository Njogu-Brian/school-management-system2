import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { StudentSummaryWidgetData } from './types';

export interface StudentSummaryWidgetsProps {
  widgets: StudentSummaryWidgetData[];
}

export const StudentSummaryWidgets: React.FC<StudentSummaryWidgetsProps> = ({ widgets }) => {
  const { palette, spacing, fontSizes, radius, shadows, colors } = useTheme();

  if (widgets.length === 0) return null;

  return (
    <View style={[styles.grid, { gap: spacing.sm }]}>
      {widgets.map((w) => (
        <View
          key={w.id}
          style={[
            styles.cell,
            {
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderRadius: radius.md,
              padding: spacing.md,
              flex: 1,
              minWidth: '46%',
            },
            shadows.sm,
          ]}
        >
          <View style={styles.row}>
            {w.icon ? (
              <Ionicons
                name={w.icon as keyof typeof Ionicons.glyphMap}
                size={18}
                color={colors.primary}
                style={{ marginRight: 6 }}
              />
            ) : null}
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
              {w.label}
            </Text>
          </View>
          <Text
            style={{
              color: palette.textPrimary,
              fontSize: fontSizes.lg,
              fontWeight: '700',
              marginTop: 4,
            }}
          >
            {w.value}
          </Text>
          {w.delta ? (
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {w.delta}
            </Text>
          ) : null}
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap' },
  cell: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center' },
});
