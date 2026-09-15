import React from 'react';
import { StyleSheet, View } from 'react-native';
import { useAdaptiveLayout } from '../layout/useAdaptiveLayout';
import { useTheme } from '../theme/ThemeContext';

export interface WidgetGridProps {
  children: React.ReactNode;
  /** Override column count. Default follows phone/tablet breakpoints. */
  columns?: 1 | 2 | 3 | 4;
}

/**
 * Responsive grid for KPI-style widgets. Children should be equal-width cells.
 */
export const WidgetGrid: React.FC<WidgetGridProps> = ({ children, columns }) => {
  const { spacing } = useTheme();
  const { gridColumns } = useAdaptiveLayout();
  const cols = columns ?? gridColumns;
  const kids = React.Children.toArray(children);
  const widthPercent =
    cols === 4 ? '24%' : cols === 3 ? '32%' : cols === 1 ? '100%' : '48%';

  return (
    <View style={[styles.grid, { gap: spacing.md }]}>
      {kids.map((child, index) => (
        <View
          key={index}
          style={[styles.cell, { width: widthPercent, flexGrow: 1 }]}
        >
          {child}
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  cell: {},
});
