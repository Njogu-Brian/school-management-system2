import React from 'react';
import { StyleSheet, View } from 'react-native';
import { SkeletonLoader } from './SkeletonLoader';
import { useTheme } from '../theme/ThemeContext';

export interface SkeletonListRowsProps {
  count?: number;
  /** Matches list item with avatar (students, staff). */
  variant?: 'avatar' | 'compact' | 'card';
}

export const SkeletonListRows: React.FC<SkeletonListRowsProps> = ({
  count = 6,
  variant = 'avatar',
}) => {
  const { spacing, palette, radius } = useTheme();

  return (
    <View style={{ paddingTop: spacing.sm }}>
      {Array.from({ length: count }).map((_, i) => (
        <View
          key={i}
          style={[
            styles.row,
            {
              marginBottom: spacing.sm,
              padding: spacing.sm,
              borderRadius: radius.card,
              borderColor: palette.borderSubtle,
              backgroundColor: palette.surfaceRaised,
            },
          ]}
        >
          {variant === 'avatar' ? (
            <View style={styles.avatarRow}>
              <SkeletonLoader width={48} height={48} borderRadius={24} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <SkeletonLoader height={16} width="55%" />
                <SkeletonLoader height={12} width="40%" style={{ marginTop: 8 }} />
                <SkeletonLoader height={12} width="65%" style={{ marginTop: 6 }} />
              </View>
            </View>
          ) : variant === 'card' ? (
            <>
              <SkeletonLoader height={18} width="45%" />
              <SkeletonLoader height={14} width="80%" style={{ marginTop: 10 }} />
              <SkeletonLoader height={14} width="60%" style={{ marginTop: 6 }} />
            </>
          ) : (
            <>
              <SkeletonLoader height={16} width="70%" />
              <SkeletonLoader height={12} width="50%" style={{ marginTop: 8 }} />
            </>
          )}
        </View>
      ))}
    </View>
  );
};

export const SkeletonWidgetGrid: React.FC<{ count?: number }> = ({ count = 4 }) => {
  const { spacing, palette, radius } = useTheme();

  return (
    <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
      {Array.from({ length: count }).map((_, i) => (
        <View
          key={i}
          style={{
            width: '47%',
            minHeight: 112,
            padding: spacing.md,
            borderRadius: radius.card,
            borderWidth: StyleSheet.hairlineWidth,
            borderColor: palette.borderSubtle,
            backgroundColor: palette.surfaceRaised,
          }}
        >
          <SkeletonLoader height={12} width="50%" />
          <SkeletonLoader height={28} width="70%" style={{ marginTop: spacing.sm }} />
          <SkeletonLoader height={12} width="40%" style={{ marginTop: spacing.xs }} />
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  avatarRow: { flexDirection: 'row', alignItems: 'center' },
});
