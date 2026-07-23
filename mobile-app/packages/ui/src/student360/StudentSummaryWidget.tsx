import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Soft3DIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { StudentSummaryWidgetData } from './types';

export interface StudentSummaryWidgetsProps {
  widgets: StudentSummaryWidgetData[];
}

export const StudentSummaryWidgets: React.FC<StudentSummaryWidgetsProps> = ({ widgets }) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();

  if (widgets.length === 0) return null;

  return (
    <View style={[styles.grid, { gap: spacing.sm }]}>
      {widgets.map((w) => (
        <View
          key={w.id}
          style={[
            styles.cell,
            elevation[1],
            {
              backgroundColor: palette.surfaceRaised,
              borderColor: palette.borderSubtle,
              borderRadius: radius.card,
              padding: spacing.md,
              flex: 1,
              minWidth: '46%',
            },
          ]}
        >
          <View style={styles.row}>
            {w.icon ? <Soft3DIcon name={w.icon as never} size={28} /> : null}
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.overline.fontSize,
                lineHeight: typography.overline.lineHeight,
                letterSpacing: typography.overline.letterSpacing,
                fontWeight: typography.overline.fontWeight,
                marginLeft: w.icon ? 8 : 0,
                textTransform: 'uppercase',
                flex: 1,
              }}
              numberOfLines={1}
            >
              {w.label}
            </Text>
          </View>
          <Text
            style={{
              color: palette.textMain,
              fontSize: typography.title.fontSize,
              fontWeight: '800',
              marginTop: spacing.sm,
            }}
            numberOfLines={2}
          >
            {w.value}
          </Text>
          {w.delta ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: 2,
              }}
              numberOfLines={2}
            >
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
