import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export type DashboardHeroVariant = 'default' | 'finance' | 'academics' | 'admissions' | 'people' | 'students' | 'settings';

const VARIANT_ICONS: Record<DashboardHeroVariant, keyof typeof Ionicons.glyphMap> = {
  default: 'grid-outline',
  finance: 'wallet-outline',
  academics: 'school-outline',
  admissions: 'person-add-outline',
  people: 'people-outline',
  students: 'people-circle-outline',
  settings: 'settings-outline',
};

export interface DashboardHeroProps {
  title: string;
  subtitle?: string;
  /** Optional stat line shown below subtitle, e.g. "5 KPIs updated today" */
  meta?: string;
  variant?: DashboardHeroVariant;
  style?: ViewStyle;
}

/** Gradient hero banner for module dashboards — V2 visual anchor. */
export const DashboardHero: React.FC<DashboardHeroProps> = ({
  title,
  subtitle,
  meta,
  variant = 'default',
  style,
}) => {
  const { colors, spacing, typography, radius, elevation, isDark } = useTheme();

  const gradientColors = isDark
    ? ([colors.primaryDark, colors.surfaceDark] as const)
    : ([colors.primary, colors.primaryLight] as const);

  return (
    <View style={[styles.wrap, { marginBottom: spacing.lg }, elevation[2], style]}>
      <LinearGradient
        colors={gradientColors}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[styles.gradient, { borderRadius: radius.card, padding: spacing.lg }]}
      >
        <View style={styles.row}>
          <View style={[styles.iconCircle, { backgroundColor: `${colors.white}22` }]}>
            <Ionicons name={VARIANT_ICONS[variant]} size={24} color={colors.white} />
          </View>
          <View style={styles.textCol}>
            <Text
              style={[
                styles.title,
                {
                  color: colors.white,
                  fontSize: typography.heading.fontSize,
                  lineHeight: typography.heading.lineHeight,
                },
              ]}
            >
              {title}
            </Text>
            {subtitle ? (
              <Text
                style={[
                  styles.subtitle,
                  {
                    color: `${colors.white}cc`,
                    fontSize: typography.body.fontSize,
                    marginTop: spacing.xs,
                  },
                ]}
              >
                {subtitle}
              </Text>
            ) : null}
          </View>
        </View>
        {meta ? (
          <View
            style={[
              styles.metaPill,
              {
                backgroundColor: `${colors.white}18`,
                borderRadius: radius.full,
                marginTop: spacing.md,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.xs,
              },
            ]}
          >
            <Text style={{ color: `${colors.white}ee`, fontSize: typography.caption.fontSize }}>
              {meta}
            </Text>
          </View>
        ) : null}
      </LinearGradient>
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {},
  gradient: { overflow: 'hidden' },
  row: { flexDirection: 'row', alignItems: 'flex-start' },
  iconCircle: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  textCol: { flex: 1 },
  title: { fontWeight: '700' },
  subtitle: { lineHeight: 20 },
  metaPill: { alignSelf: 'flex-start' },
});
