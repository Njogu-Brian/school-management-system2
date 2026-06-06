import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { DashboardHero } from '../dashboard/DashboardHero';
import { useTheme } from '../theme/ThemeContext';
import type { SettingsSectionId, SettingsSectionTab } from './types';

export interface SettingsHubLayoutProps {
  sections: SettingsSectionTab[];
  activeSection: SettingsSectionId;
  onSectionChange: (id: SettingsSectionId) => void;
  children: React.ReactNode;
  schoolName?: string;
  schoolSubtitle?: string;
  footerLinks?: Array<{ id: string; label: string; icon: keyof typeof Ionicons.glyphMap; onPress: () => void }>;
}

export const SettingsHubLayout: React.FC<SettingsHubLayoutProps> = ({
  sections,
  activeSection,
  onSectionChange,
  children,
  schoolName = 'School settings',
  schoolSubtitle = 'Administration & configuration',
  footerLinks = [],
}) => {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View style={styles.flex}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.sm }}>
        <DashboardHero
          variant="settings"
          title={schoolName}
          subtitle={schoolSubtitle}
        />

        <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
          {sections.map((section) => {
            const active = section.id === activeSection;
            return (
              <Pressable
                key={section.id}
                onPress={() => onSectionChange(section.id)}
                accessibilityRole="button"
                accessibilityState={{ selected: active }}
                style={({ pressed }) => [
                  styles.card,
                  elevation[1],
                  {
                    backgroundColor: active ? `${colors.primary}08` : palette.surfaceRaised,
                    borderColor: active ? colors.primary : palette.borderSubtle,
                    borderRadius: radius.card,
                    padding: spacing.md,
                    opacity: pressed ? 0.92 : 1,
                  },
                ]}
              >
                <View style={styles.cardRow}>
                  <View
                    style={[
                      styles.iconWrap,
                      {
                        backgroundColor: active ? `${colors.primary}18` : palette.surfaceMuted,
                        borderRadius: radius.sm,
                      },
                    ]}
                  >
                    <Ionicons
                      name={section.icon as keyof typeof Ionicons.glyphMap}
                      size={20}
                      color={active ? colors.primary : palette.textSecondary}
                    />
                  </View>
                  <Text
                    style={{
                      flex: 1,
                      color: palette.textPrimary,
                      fontSize: typography.body.fontSize,
                      fontWeight: active ? '700' : '600',
                    }}
                  >
                    {section.label}
                  </Text>
                  <Ionicons
                    name={active ? 'chevron-down' : 'chevron-forward'}
                    size={18}
                    color={palette.textMuted}
                  />
                </View>
              </Pressable>
            );
          })}

          {footerLinks.map((link) => (
            <Pressable
              key={link.id}
              onPress={link.onPress}
              style={({ pressed }) => [
                styles.card,
                elevation[1],
                {
                  backgroundColor: palette.surfaceRaised,
                  borderColor: palette.borderSubtle,
                  borderRadius: radius.card,
                  padding: spacing.md,
                  opacity: pressed ? 0.92 : 1,
                },
              ]}
            >
              <View style={styles.cardRow}>
                <View
                  style={[
                    styles.iconWrap,
                    { backgroundColor: palette.surfaceMuted, borderRadius: radius.sm },
                  ]}
                >
                  <Ionicons name={link.icon} size={20} color={palette.textSecondary} />
                </View>
                <Text
                  style={{
                    flex: 1,
                    color: palette.textPrimary,
                    fontSize: typography.body.fontSize,
                    fontWeight: '600',
                  }}
                >
                  {link.label}
                </Text>
                <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
              </View>
            </Pressable>
          ))}
        </View>
      </View>

      <View style={{ flex: 1, paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        {children}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  card: { borderWidth: StyleSheet.hairlineWidth },
  cardRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  iconWrap: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
