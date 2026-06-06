import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ScrollableTabBar } from '../layout/ScrollableTabBar';
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
  const { spacing } = useTheme();

  return (
    <View style={styles.flex}>
      <ScrollableTabBar
        variant="scroll"
        tabs={sections.map((s) => ({
          key: s.id,
          label: s.label,
          icon: s.icon as keyof typeof Ionicons.glyphMap,
        }))}
        activeTab={activeSection}
        onTabChange={(id) => onSectionChange(id as SettingsSectionId)}
        style={{ paddingHorizontal: spacing.md }}
      />

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
});
