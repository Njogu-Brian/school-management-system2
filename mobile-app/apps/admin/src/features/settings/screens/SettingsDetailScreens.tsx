import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import type { SettingsStackParamList } from '../../../navigation/settingsStackTypes';
import { AcademicSettingsSection } from '../sections/AcademicSettingsSection';
import { GradingSettingsSection } from '../sections/GradingSettingsSection';
import { RolesSettingsSection } from '../sections/RolesSettingsSection';
import { SchoolSettingsSection } from '../sections/SchoolSettingsSection';
import { AboutScreen } from './AboutScreen';
import { SessionScreen } from './SessionScreen';

function DetailShell({
  title,
  subtitle,
  onBack,
  children,
}: {
  title: string;
  subtitle: string;
  onBack: () => void;
  children: React.ReactNode;
}) {
  const { spacing } = useTheme();
  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={title} subtitle={subtitle} onBack={onBack} />
      {children}
    </ScreenContainer>
  );
}

type SchoolProps = StackScreenProps<SettingsStackParamList, 'SettingsSchool'>;
export const SchoolSettingsScreen: React.FC<SchoolProps> = ({ navigation }) => (
  <DetailShell
    title="School"
    subtitle="Identity, branding, and regional defaults. Read-only on mobile."
    onBack={() => navigation.goBack()}
  >
    <SchoolSettingsSection />
  </DetailShell>
);

type AcademicProps = StackScreenProps<SettingsStackParamList, 'SettingsAcademic'>;
export const AcademicSettingsScreen: React.FC<AcademicProps> = ({ navigation }) => (
  <DetailShell
    title="Academic"
    subtitle="Years, terms, classes, and subjects. Read-only on mobile."
    onBack={() => navigation.goBack()}
  >
    <AcademicSettingsSection />
  </DetailShell>
);

type GradingProps = StackScreenProps<SettingsStackParamList, 'SettingsGrading'>;
export const GradingSettingsScreen: React.FC<GradingProps> = ({ navigation }) => (
  <DetailShell
    title="Grading"
    subtitle="Schemes, bands, and exam types. Read-only on mobile."
    onBack={() => navigation.goBack()}
  >
    <GradingSettingsSection />
  </DetailShell>
);

type RolesProps = StackScreenProps<SettingsStackParamList, 'SettingsRoles'>;
export const RolesSettingsScreen: React.FC<RolesProps> = ({ navigation }) => (
  <DetailShell
    title="Roles"
    subtitle="Permissions are read-only here. Edit them on the web portal."
    onBack={() => navigation.goBack()}
  >
    <RolesSettingsSection />
  </DetailShell>
);

type SessionProps = StackScreenProps<SettingsStackParamList, 'SettingsSession'>;
export const SessionSettingsScreen: React.FC<SessionProps> = ({ navigation }) => (
  <SessionScreen onBack={() => navigation.goBack()} />
);

type AboutProps = StackScreenProps<SettingsStackParamList, 'SettingsAbout'>;
export const AboutSettingsScreen: React.FC<AboutProps> = ({ navigation }) => (
  <AboutScreen onBack={() => navigation.goBack()} />
);
