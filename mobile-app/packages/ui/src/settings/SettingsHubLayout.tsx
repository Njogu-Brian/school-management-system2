import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { SettingsSectionId, SettingsSectionTab } from './types';

export interface SettingsHubLayoutProps {
  sections: SettingsSectionTab[];
  activeSection: SettingsSectionId;
  onSectionChange: (id: SettingsSectionId) => void;
  children: React.ReactNode;
}

export const SettingsHubLayout: React.FC<SettingsHubLayoutProps> = ({
  sections,
  activeSection,
  onSectionChange,
  children,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={styles.flex}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={[styles.nav, { paddingHorizontal: spacing.md, gap: spacing.xs }]}
      >
        {sections.map((section) => {
          const active = section.id === activeSection;
          return (
            <Pressable
              key={section.id}
              onPress={() => onSectionChange(section.id)}
              style={[
                styles.chip,
                {
                  backgroundColor: active ? `${colors.primary}18` : palette.surface,
                  borderColor: active ? colors.primary : palette.border,
                  borderRadius: radius.full,
                  paddingHorizontal: spacing.md,
                  paddingVertical: spacing.xs,
                },
              ]}
            >
              <View style={styles.chipInner}>
                <Ionicons
                  name={section.icon as keyof typeof Ionicons.glyphMap}
                  size={14}
                  color={active ? colors.primary : palette.textSecondary}
                />
                <Text
                  style={{
                    color: active ? colors.primary : palette.textSecondary,
                    fontSize: fontSizes.xs,
                    fontWeight: '700',
                    marginLeft: 4,
                  }}
                >
                  {section.label}
                </Text>
              </View>
            </Pressable>
          );
        })}
      </ScrollView>

      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        keyboardShouldPersistTaps="handled"
      >
        {children}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  nav: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8 },
  chip: { borderWidth: StyleSheet.hairlineWidth },
  chipInner: { flexDirection: 'row', alignItems: 'center' },
});
