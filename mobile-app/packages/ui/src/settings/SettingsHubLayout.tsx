import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';

export interface SettingsHubRow {
  id: string;
  label: string;
  subtitle?: string;
  icon: keyof typeof Ionicons.glyphMap;
  tone?: AccentTone;
  onPress: () => void;
}

export interface SettingsHubGroup {
  title: string;
  rows: SettingsHubRow[];
}

export interface SettingsHubLayoutProps {
  schoolName?: string;
  schoolSubtitle?: string;
  meta?: string;
  appearance?: React.ReactNode;
  groups: SettingsHubGroup[];
}

/**
 * iOS-style grouped settings hub. Rows push onto a stack so the app header
 * and floating tab bar stay visible.
 */
export const SettingsHubLayout: React.FC<SettingsHubLayoutProps> = ({
  schoolName = 'Settings',
  schoolSubtitle = 'Administration & configuration',
  meta = 'Read-only on mobile',
  appearance,
  groups,
}) => {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.sm, gap: spacing.lg }}>
      <View
        style={[
          styles.identity,
          elevation[1],
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            borderRadius: radius.card,
            padding: spacing.md,
          },
        ]}
      >
        <AccentIcon name="business-outline" tone="blue" size={48} iconSize={22} />
        <View style={{ flex: 1, minWidth: 0 }}>
          <Text
            numberOfLines={1}
            style={{
              color: palette.textMain,
              fontSize: typography.title.fontSize,
              lineHeight: typography.title.lineHeight,
              fontWeight: '800',
              letterSpacing: -0.3,
            }}
          >
            {schoolName}
          </Text>
          <Text
            numberOfLines={2}
            style={{
              color: palette.textSub,
              fontSize: typography.caption.fontSize,
              lineHeight: typography.caption.lineHeight,
              marginTop: 2,
            }}
          >
            {schoolSubtitle}
          </Text>
        </View>
        <View
          style={{
            backgroundColor: palette.primaryMuted,
            borderRadius: 999,
            paddingHorizontal: spacing.sm,
            paddingVertical: 4,
          }}
        >
          <Text style={{ color: colors.primary, fontSize: 11, fontWeight: '700' }}>{meta}</Text>
        </View>
      </View>

      {appearance}

      {groups.map((group) => (
        <View key={group.title} style={{ gap: spacing.sm }}>
          <Text
            style={{
              color: palette.textMuted,
              fontSize: typography.overline.fontSize,
              lineHeight: typography.overline.lineHeight,
              fontWeight: typography.overline.fontWeight,
              letterSpacing: typography.overline.letterSpacing,
              textTransform: 'uppercase',
              marginLeft: spacing.xs,
            }}
          >
            {group.title}
          </Text>
          <View
            style={[
              elevation[1],
              {
                backgroundColor: palette.surfaceRaised,
                borderColor: palette.borderSubtle,
                borderWidth: StyleSheet.hairlineWidth,
                borderRadius: radius.card,
                overflow: 'hidden',
              },
            ]}
          >
            {group.rows.map((row, index) => (
              <View key={row.id}>
                {index > 0 ? (
                  <View style={{ height: StyleSheet.hairlineWidth, backgroundColor: palette.borderSubtle, marginLeft: 64 }} />
                ) : null}
                <Pressable
                  onPress={row.onPress}
                  accessibilityRole="button"
                  accessibilityLabel={row.label}
                  style={({ pressed }) => [
                    styles.row,
                    {
                      paddingHorizontal: spacing.md,
                      paddingVertical: spacing.sm,
                      minHeight: 56,
                      opacity: pressed ? 0.88 : 1,
                      backgroundColor: pressed ? palette.surfaceMuted : 'transparent',
                    },
                  ]}
                >
                  <AccentIcon
                    name={row.icon}
                    tone={row.tone ?? 'blue'}
                    size={40}
                    iconSize={18}
                  />
                  <View style={{ flex: 1, minWidth: 0 }}>
                    <Text
                      style={{
                        color: palette.textMain,
                        fontSize: typography.body.fontSize,
                        lineHeight: typography.body.lineHeight,
                        fontWeight: '600',
                      }}
                    >
                      {row.label}
                    </Text>
                    {row.subtitle ? (
                      <Text
                        numberOfLines={1}
                        style={{
                          color: palette.textSub,
                          fontSize: typography.caption.fontSize,
                          marginTop: 1,
                        }}
                      >
                        {row.subtitle}
                      </Text>
                    ) : null}
                  </View>
                  <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
                </Pressable>
              </View>
            ))}
          </View>
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  identity: {
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
});
