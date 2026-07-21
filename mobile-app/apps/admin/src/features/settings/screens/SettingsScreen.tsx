import { getNavArea, useCan, useSchoolSettings } from '@erp/core';
import {
  PlaceholderScreen,
  ScreenContainer,
  SettingsHubLayout,
  useTheme,
  type SettingsSectionId,
} from '@erp/ui';
import { useRoute, type RouteProp } from '@react-navigation/native';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Modal, ScrollView } from 'react-native';
import { ApiDiagnosticsScreen } from '../../diagnostics';
import type { DrawerParamList } from '../../../navigation/types';
import { AcademicSettingsSection } from '../sections/AcademicSettingsSection';
import { GradingSettingsSection } from '../sections/GradingSettingsSection';
import { RolesSettingsSection } from '../sections/RolesSettingsSection';
import { SchoolSettingsSection } from '../sections/SchoolSettingsSection';
import { AboutScreen } from './AboutScreen';
import { GeofenceSettingsScreen } from './GeofenceSettingsScreen';
import { SessionScreen } from './SessionScreen';

const area = getNavArea('settings');

const ALL_SECTIONS = [
  { id: 'school' as const, label: 'School', icon: 'business-outline' },
  { id: 'academic' as const, label: 'Academic', icon: 'calendar-outline' },
  { id: 'grading' as const, label: 'Grading', icon: 'ribbon-outline' },
  { id: 'roles' as const, label: 'Roles', icon: 'shield-checkmark-outline' },
];

export const SettingsScreen: React.FC = () => {
  const route = useRoute<RouteProp<DrawerParamList, 'Settings'>>();
  const { spacing } = useTheme();
  const canView = useCan('settings.view');
  const schoolQuery = useSchoolSettings({ enabled: canView });
  const [activeSection, setActiveSection] = useState<SettingsSectionId>('school');
  const [diagnosticsOpen, setDiagnosticsOpen] = useState(false);
  const [sessionOpen, setSessionOpen] = useState(false);
  const [geofenceOpen, setGeofenceOpen] = useState(false);
  const [aboutOpen, setAboutOpen] = useState(false);

  const resetHub = useCallback(() => {
    setActiveSection('school');
    setDiagnosticsOpen(false);
    setSessionOpen(false);
    setGeofenceOpen(false);
    setAboutOpen(false);
  }, []);

  useEffect(() => {
    if (route.params?.resetAt != null) {
      resetHub();
    }
  }, [route.params?.resetAt, resetHub]);

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

  const schoolName = schoolQuery.data?.school_name?.trim() || 'Settings';
  const schoolSubtitle =
    schoolQuery.data?.school_email?.trim() || 'Administration & configuration';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        style={{ flex: 1 }}
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ paddingBottom: spacing.xl }}
      >
        <SettingsHubLayout
          sections={sections}
          activeSection={activeSection}
          onSectionChange={setActiveSection}
          schoolName={schoolName}
          schoolSubtitle={schoolSubtitle}
          meta="Read-only on mobile"
          footerLinks={[
            {
              id: 'geofence',
              label: 'Staff geofence',
              icon: 'location-outline',
              onPress: () => setGeofenceOpen(true),
            },
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
      <Modal visible={geofenceOpen} animationType="slide" onRequestClose={() => setGeofenceOpen(false)}>
        <GeofenceSettingsScreen onBack={() => setGeofenceOpen(false)} />
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
