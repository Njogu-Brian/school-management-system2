import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { ScreenContainer } from '../layout/ScreenContainer';
import { useTheme } from '../theme/ThemeContext';

export interface PlaceholderScreenProps {
  title: string;
  description?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  /** Static list of planned sub-areas for this module (descriptive copy, not data). */
  sections?: string[];
}

/**
 * Foundation placeholder for a module that has navigation wired but no features yet.
 * Renders the module identity + a "coming in a future sprint" note + planned structure.
 * Contains no business logic, API calls, or mock data.
 */
export const PlaceholderScreen: React.FC<PlaceholderScreenProps> = ({
  title,
  description,
  icon = 'cube-outline',
  sections,
}) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <ScreenContainer contentContainerStyle={styles.content}>
      <View
        style={[
          styles.iconCircle,
          { backgroundColor: palette.accent, marginBottom: spacing.lg },
        ]}
      >
        <Ionicons name={icon} size={40} color={colors.primary} />
      </View>

      <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.xxl }]}>
        {title}
      </Text>

      {description ? (
        <Text
          style={[
            styles.description,
            { color: palette.textSecondary, fontSize: fontSizes.md, marginTop: spacing.sm },
          ]}
        >
          {description}
        </Text>
      ) : null}

      <View
        style={[
          styles.badge,
          {
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderRadius: radius.full,
            marginTop: spacing.lg,
          },
        ]}
      >
        <Ionicons name="construct-outline" size={14} color={colors.warning} />
        <Text style={[styles.badgeText, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
          Foundation ready — module arrives in a future sprint
        </Text>
      </View>

      {sections && sections.length > 0 ? (
        <View
          style={[
            styles.card,
            {
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderRadius: radius.xl,
              marginTop: spacing.xl,
              padding: spacing.md,
            },
            shadows.sm,
          ]}
        >
          <Text
            style={[
              styles.cardTitle,
              { color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm },
            ]}
          >
            PLANNED IN THIS MODULE
          </Text>
          {sections.map((section) => (
            <View key={section} style={styles.row}>
              <Ionicons name="ellipse" size={6} color={colors.primary} />
              <Text
                style={[styles.rowText, { color: palette.textPrimary, fontSize: fontSizes.sm }]}
              >
                {section}
              </Text>
            </View>
          ))}
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: { alignItems: 'center', justifyContent: 'center', padding: 24 },
  iconCircle: {
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: { fontWeight: '700', textAlign: 'center' },
  description: { textAlign: 'center', maxWidth: 320, lineHeight: 22 },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  badgeText: { marginLeft: 6, fontWeight: '600' },
  card: { width: '100%', maxWidth: 420 },
  cardTitle: { fontWeight: '700', letterSpacing: 0.5 },
  row: { flexDirection: 'row', alignItems: 'center', paddingVertical: 5 },
  rowText: { marginLeft: 10 },
});
