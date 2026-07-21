import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useEffect } from 'react';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withTiming,
} from 'react-native-reanimated';
import { useTheme } from '../theme/ThemeContext';
import { useReducedMotion } from '../theme/useReducedMotion';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';

export type DashboardHeroVariant =
  | 'default'
  | 'finance'
  | 'academics'
  | 'admissions'
  | 'people'
  | 'students'
  | 'settings'
  | 'communication'
  | 'operations'
  | 'reports';

const VARIANT_ICONS: Record<DashboardHeroVariant, keyof typeof Ionicons.glyphMap> = {
  default: 'grid',
  finance: 'wallet',
  academics: 'school',
  admissions: 'person-add',
  people: 'people',
  students: 'people-circle',
  settings: 'settings',
  communication: 'megaphone',
  operations: 'bus',
  reports: 'bar-chart',
};

const VARIANT_TONE: Record<DashboardHeroVariant, AccentTone> = {
  default: 'blue',
  finance: 'emerald',
  academics: 'violet',
  admissions: 'amber',
  people: 'cyan',
  students: 'indigo',
  settings: 'teal',
  communication: 'rose',
  operations: 'amber',
  reports: 'blue',
};

export interface DashboardHeroProps {
  title: string;
  subtitle?: string;
  meta?: string;
  /** Personalized greeting line, e.g. "Good morning" */
  greeting?: string;
  /** Display name under greeting */
  userName?: string;
  variant?: DashboardHeroVariant;
  style?: ViewStyle;
}

/** Flagship hero — tall gradient, greeting, large type, accent icon. */
export const DashboardHero: React.FC<DashboardHeroProps> = ({
  title,
  subtitle,
  meta,
  greeting,
  userName,
  variant = 'default',
  style,
}) => {
  const { colors, spacing, typography, radius, motion, isDark } = useTheme();
  const reduceMotion = useReducedMotion();
  const opacity = useSharedValue(reduceMotion ? 1 : 0);
  const translateY = useSharedValue(reduceMotion ? 0 : 12);

  useEffect(() => {
    if (reduceMotion) {
      opacity.value = 1;
      translateY.value = 0;
      return;
    }
    opacity.value = withTiming(1, { duration: motion.duration.slow });
    translateY.value = withDelay(
      40,
      withTiming(0, { duration: motion.duration.slow }),
    );
  }, [motion.duration.slow, opacity, reduceMotion, translateY]);

  const animStyle = useAnimatedStyle(() => ({
    opacity: opacity.value,
    transform: [{ translateY: translateY.value }],
  }));

  const gradientColors = isDark
    ? ([colors.primaryDark, '#0d1b2a', colors.backgroundDark] as const)
    : ([colors.primary, colors.primaryLight, '#3b82f6'] as const);

  return (
    <Animated.View style={[styles.wrap, { marginBottom: spacing.lg }, animStyle, style]}>
      <LinearGradient
        colors={[...gradientColors]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[
          styles.gradient,
          {
            borderRadius: radius.xl,
            padding: spacing.lg,
            paddingTop: spacing.xl,
            paddingBottom: spacing.xl,
          },
        ]}
      >
        <View style={styles.orb} pointerEvents="none" />
        <View style={styles.orbSmall} pointerEvents="none" />

        {greeting || userName ? (
          <View style={{ marginBottom: spacing.md }}>
            {greeting ? (
              <Text
                style={{
                  color: 'rgba(255,255,255,0.78)',
                  fontSize: typography.body.fontSize,
                  fontWeight: '500',
                }}
              >
                {greeting}
              </Text>
            ) : null}
            {userName ? (
              <Text
                style={{
                  color: colors.white,
                  fontSize: typography.display.fontSize,
                  lineHeight: typography.display.lineHeight,
                  fontWeight: '800',
                  letterSpacing: 0.5,
                  textTransform: 'uppercase',
                }}
                numberOfLines={1}
              >
                {userName}
              </Text>
            ) : null}
          </View>
        ) : null}

        <View style={styles.row}>
          <AccentIcon
            name={VARIANT_ICONS[variant]}
            tone={VARIANT_TONE[variant]}
            size={56}
            iconSize={26}
          />
          <View style={[styles.textCol, { marginLeft: spacing.md }]}>
            <Text
              style={{
                color: colors.white,
                fontSize: typography.headlineLarge.fontSize,
                lineHeight: typography.headlineLarge.lineHeight,
                fontWeight: '700',
              }}
            >
              {title}
            </Text>
            {subtitle ? (
              <Text
                style={{
                  color: 'rgba(255,255,255,0.82)',
                  fontSize: typography.body.fontSize,
                  marginTop: spacing.xs,
                  lineHeight: 22,
                }}
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
                backgroundColor: 'rgba(255,255,255,0.14)',
                borderRadius: radius.full,
                marginTop: spacing.lg,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.sm,
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: 'rgba(255,255,255,0.22)',
              },
            ]}
          >
            <Text style={{ color: colors.white, fontSize: typography.caption.fontSize, fontWeight: '600' }}>
              {meta}
            </Text>
          </View>
        ) : null}
      </LinearGradient>
    </Animated.View>
  );
};

const styles = StyleSheet.create({
  wrap: {},
  gradient: { overflow: 'hidden', minHeight: 168 },
  row: { flexDirection: 'row', alignItems: 'center' },
  textCol: { flex: 1 },
  metaPill: { alignSelf: 'flex-start' },
  orb: {
    position: 'absolute',
    width: 180,
    height: 180,
    borderRadius: 90,
    backgroundColor: 'rgba(255,255,255,0.08)',
    top: -40,
    right: -30,
  },
  orbSmall: {
    position: 'absolute',
    width: 90,
    height: 90,
    borderRadius: 45,
    backgroundColor: 'rgba(20,184,166,0.18)',
    bottom: -20,
    left: 40,
  },
});
