import { getNavArea, useCan, useSchoolSettings } from '@erp/core';
import { PlaceholderScreen, ScreenContainer, SettingsHubLayout, type SettingsSectionId } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { Modal, ScrollView } from 'react-native';
import { ApiDiagnosticsScreen } from '../../diagnostics';
import { AcademicSettingsSection } from '../sections/AcademicSettingsSection';
import { GradingSettingsSection } from '../sections/GradingSettingsSection';
import { RolesSettingsSection } from '../sections/RolesSettingsSection';
import { SchoolSettingsSection } from '../sections/SchoolSettingsSection';
import { AboutScreen } from './AboutScreen';
import { SessionScreen } from './SessionScreen';

const area = getNavArea('settings');

const ALL_SECTIONS = [
  { id: 'school' as const, label: 'School', icon: 'business-outline' },
  { id: 'academic' as const, label: 'Academic', icon: 'calendar-outline' },
  { id: 'grading' as const, label: 'Grading', icon: 'ribbon-outline' },
  { id: 'roles' as const, label: 'Roles', icon: 'shield-checkmark-outline' },
];

export const SettingsScreen: React.FC = () => {
  const canView = useCan('settings.view');
  const schoolQuery = useSchoolSettings({ enabled: canView });
  const [activeSection, setActiveSection] = useState<SettingsSectionId>('school');
  const [diagnosticsOpen, setDiagnosticsOpen] = useState(false);
  const [sessionOpen, setSessionOpen] = useState(false);
  const [aboutOpen, setAboutOpen] = useState(false);

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
      <ScrollView style={{ flex: 1 }} keyboardShouldPersistTaps="handled">
        <SettingsHubLayout
          sections={sections}
          activeSection={activeSection}
          onSectionChange={setActiveSection}
          schoolName={schoolQuery.data?.school_name ?? 'School settings'}
          schoolSubtitle={schoolQuery.data?.school_email ?? 'Administration & configuration'}
          footerLinks={[
            {
              id: 'session',
              label: 'Session & security',
              icon: 'lock-closed-outline',
              onPress: () => setSessionOpen(true),
            },
            {
              id: 'about',
              label: 'About & support',
              icon: 'information-circle-outline',
              onPress: () => setAboutOpen(true),
            },
            ...(__DEV__
              ? [
                  {
                    id: 'diagnostics',
                    label: 'API Health (dev)',
                    icon: 'pulse-outline' as const,
                    onPress: () => setDiagnosticsOpen(true),
                  },
                ]
              : []),
          ]}
        >
          {content}
        </SettingsHubLayout>
      </ScrollView>
      <Modal visible={sessionOpen} animationType="slide" onRequestClose={() => setSessionOpen(false)}>
        <SessionScreen onBack={() => setSessionOpen(false)} />
      </Modal>
      <Modal visible={aboutOpen} animationType="slide" onRequestClose={() => setAboutOpen(false)}>
        <AboutScreen onBack={() => setAboutOpen(false)} />
      </Modal>
      {__DEV__ ? (
        <Modal visible={diagnosticsOpen} animationType="slide" onRequestClose={() => setDiagnosticsOpen(false)}>
          <ApiDiagnosticsScreen onClose={() => setDiagnosticsOpen(false)} />
        </Modal>
      ) : null}
    </ScreenContainer>
  );
};
