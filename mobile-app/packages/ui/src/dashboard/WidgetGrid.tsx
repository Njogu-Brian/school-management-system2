import React from 'react';
import { StyleSheet, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface WidgetGridProps {
  children: React.ReactNode;
  /** Number of columns (default 2 on phone). */
  columns?: 1 | 2;
}

/**
 * Responsive grid for KPI-style widgets. Children should be equal-width cells.
 */
export const WidgetGrid: React.FC<WidgetGridProps> = ({ children, columns = 2 }) => {
  const { spacing } = useTheme();
  const kids = React.Children.toArray(children);

  return (
    <View style={[styles.grid, { gap: spacing.md }]}>
      {kids.map((child, index) => (
        <View
          key={index}
          style={[styles.cell, columns === 2 ? styles.half : styles.full]}
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
  half: {
    width: '48%',
    flexGrow: 1,
  },
  full: {
    width: '100%',
  },
});
