import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface DashboardSectionProps {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  /** Optional trailing header action (e.g. "See all"). */
  headerRight?: React.ReactNode;
}

export const DashboardSection: React.FC<DashboardSectionProps> = ({
  title,
  subtitle,
  children,
  headerRight,
}) => {
  const { palette, spacing, fontSizes } = useTheme();

  return (
    <View style={[styles.section, { marginBottom: spacing.lg }]}>
      <View style={styles.header}>
        <View style={styles.headerText}>
          <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.lg }]}>
            {title}
          </Text>
          {subtitle ? (
            <Text
              style={[styles.subtitle, { color: palette.textSecondary, fontSize: fontSizes.sm }]}
            >
              {subtitle}
            </Text>
          ) : null}
        </View>
        {headerRight}
      </View>
      {children}
    </View>
  );
};

const styles = StyleSheet.create({
  section: {},
  header: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  headerText: { flex: 1 },
  title: { fontWeight: '700' },
  subtitle: { marginTop: 2 },
});
