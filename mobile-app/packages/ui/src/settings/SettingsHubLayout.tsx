import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { DashboardHero } from '../dashboard/DashboardHero';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { SettingsSectionId, SettingsSectionTab } from './types';

export interface SettingsHubLayoutProps {
  sections: SettingsSectionTab[];
  activeSection: SettingsSectionId;
  onSectionChange: (id: SettingsSectionId) => void;
  children: React.ReactNode;
  schoolName?: string;
  schoolSubtitle?: string;
  /** Hero meta pill, e.g. "Read-only on mobile" */
  meta?: string;
  footerLinks?: Array<{ id: string; label: string; icon: keyof typeof Ionicons.glyphMap; onPress: () => void }>;
}

const SECTION_TONES: Record<SettingsSectionId, AccentTone> = {
  school: 'blue',
  academic: 'teal',
  grading: 'violet',
  roles: 'indigo',
};

const FOOTER_TONES: AccentTone[] = ['cyan', 'amber', 'emerald', 'rose'];

export const SettingsHubLayout: React.FC<SettingsHubLayoutProps> = ({
  sections,
  activeSection,
  onSectionChange,
  children,
  schoolName = 'Settings',
  schoolSubtitle = 'Administration & configuration',
  meta = 'Read-only on mobile',
  footerLinks = [],
}) => {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.sm }}>
        <DashboardHero
          variant="settings"
          title={schoolName}
          subtitle={schoolSubtitle}
          meta={meta}
        />

        <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
          {sections.map((section) => {
            const active = section.id === activeSection;
            return (
              <View key={section.id}>
                <Pressable
                  onPress={() => onSectionChange(section.id)}
                  accessibilityRole="button"
                  accessibilityState={{ selected: active, expanded: active }}
                  style={({ pressed }) => [
                    styles.card,
                    elevation[2],
                    {
                      backgroundColor: active ? `${colors.primary}08` : palette.surfaceRaised,
                      borderColor: active ? colors.primary : palette.borderSubtle,
                      borderRadius: radius.card,
                      paddingHorizontal: spacing.md,
                      paddingVertical: spacing.sm,
                      minHeight: 48,
                      opacity: pressed ? 0.92 : 1,
                    },
                  ]}
                >
                  <View style={[styles.cardRow, { gap: spacing.sm }]}>
                    <AccentIcon
                      name={section.icon as keyof typeof Ionicons.glyphMap}
                      tone={SECTION_TONES[section.id] ?? 'blue'}
                      size={40}
                      iconSize={18}
                    />
                    <Text
                      style={{
                        flex: 1,
                        color: palette.textPrimary,
                        fontSize: typography.body.fontSize,
                        lineHeight: typography.body.lineHeight,
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

                {active ? (
                  <View
                    style={{
                      marginTop: spacing.sm,
                      marginBottom: spacing.xs,
                      paddingHorizontal: spacing.xs,
                    }}
                  >
                    {children}
                  </View>
                ) : null}
              </View>
            );
          })}

          {footerLinks.length > 0 ? (
            <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
              {footerLinks.map((link, index) => (
                <Pressable
                  key={link.id}
                  onPress={link.onPress}
                  accessibilityRole="button"
                  style={({ pressed }) => [
                    styles.card,
                    elevation[2],
                    {
                      backgroundColor: palette.surfaceRaised,
                      borderColor: palette.borderSubtle,
                      borderRadius: radius.card,
                      paddingHorizontal: spacing.md,
                      paddingVertical: spacing.sm,
                      minHeight: 48,
                      opacity: pressed ? 0.92 : 1,
                    },
                  ]}
                >
                  <View style={[styles.cardRow, { gap: spacing.sm }]}>
                    <AccentIcon
                      name={link.icon}
                      tone={FOOTER_TONES[index % FOOTER_TONES.length]}
                      size={40}
                      iconSize={18}
                    />
                    <Text
                      style={{
                        flex: 1,
                        color: palette.textPrimary,
                        fontSize: typography.body.fontSize,
                        lineHeight: typography.body.lineHeight,
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
          ) : null}
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, justifyContent: 'center' },
  cardRow: { flexDirection: 'row', alignItems: 'center' },
});
