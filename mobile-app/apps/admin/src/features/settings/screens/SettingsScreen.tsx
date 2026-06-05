import { getNavArea, useCan } from '@erp/core';
import { PlaceholderScreen, ScreenContainer, SettingsHubLayout, type SettingsSectionId } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { Modal, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { ApiDiagnosticsScreen } from '../../diagnostics';
import { AcademicSettingsSection } from '../sections/AcademicSettingsSection';
import { GradingSettingsSection } from '../sections/GradingSettingsSection';
import { RolesSettingsSection } from '../sections/RolesSettingsSection';
import { SchoolSettingsSection } from '../sections/SchoolSettingsSection';

const area = getNavArea('settings');

const ALL_SECTIONS = [
  { id: 'school' as const, label: 'School', icon: 'business-outline' },
  { id: 'academic' as const, label: 'Academic', icon: 'calendar-outline' },
  { id: 'grading' as const, label: 'Grading', icon: 'ribbon-outline' },
  { id: 'roles' as const, label: 'Roles', icon: 'shield-checkmark-outline' },
];

export const SettingsScreen: React.FC = () => {
  const canView = useCan('settings.view');
  const { colors, spacing, fontSizes, palette } = useTheme();
  const [activeSection, setActiveSection] = useState<SettingsSectionId>('school');
  const [diagnosticsOpen, setDiagnosticsOpen] = useState(false);

  const sections = useMemo(() => ALL_SECTIONS, []);

  if (!canView) {
    return (
      <PlaceholderScreen
        title={area.label}
        description="You need settings.view permission to open the settings hub."
        icon="lock-closed-outline"
      />
    );
  }

  const content = (() => {
    switch (activeSection) {
      case 'school':
        return <SchoolSettingsSection />;
      case 'academic':
        return <AcademicSettingsSection />;
      case 'grading':
        return <GradingSettingsSection />;
      case 'roles':
        return <RolesSettingsSection />;
      default:
        return null;
    }
  })();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <SettingsHubLayout
        sections={sections}
        activeSection={activeSection}
        onSectionChange={setActiveSection}
      >
        {content}
      </SettingsHubLayout>
      {__DEV__ ? (
        <>
          <View
            style={{
              paddingHorizontal: spacing.md,
              paddingVertical: spacing.sm,
              borderTopWidth: 1,
              borderTopColor: palette.border,
            }}
          >
            <Pressable onPress={() => setDiagnosticsOpen(true)}>
              <Text style={{ color: colors.primary, fontSize: fontSizes.sm, fontWeight: '600' }}>
                API Health (dev)
              </Text>
            </Pressable>
          </View>
          <Modal visible={diagnosticsOpen} animationType="slide" onRequestClose={() => setDiagnosticsOpen(false)}>
            <ApiDiagnosticsScreen onClose={() => setDiagnosticsOpen(false)} />
          </Modal>
        </>
      ) : null}
    </ScreenContainer>
  );
};
