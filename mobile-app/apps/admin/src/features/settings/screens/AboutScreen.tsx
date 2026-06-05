import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import Constants from 'expo-constants';
import React from 'react';
import { Linking, Pressable, Text } from 'react-native';

const SUPPORT_PHONE = '0719396233';
const SUPPORT_EMAIL = 'info@royalkingsschools.sc.ke';
const WEBSITE = 'https://royalkingsschools.sc.ke';

export interface AboutScreenProps {
  onBack?: () => void;
}

export const AboutScreen: React.FC<AboutScreenProps> = ({ onBack }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const version = Constants.expoConfig?.version ?? '1.0.0';
  const build = Constants.expoConfig?.android?.versionCode ?? Constants.nativeBuildVersion ?? '—';

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      {onBack ? <AcademicScreenHeader title="About" onBack={onBack} /> : null}
      <Text style={{ fontWeight: '700', fontSize: fontSizes.lg, color: palette.textPrimary }}>
        Royal Kings ERP Admin
      </Text>
      <Text style={{ color: palette.textSecondary, marginTop: spacing.xs }}>
        School administration on mobile.
      </Text>

      <FinanceFieldSection
        title="Build"
        rows={[
          { label: 'Version', value: version },
          { label: 'Build', value: String(build) },
          { label: 'Slug', value: Constants.expoConfig?.slug ?? 'school-erp-admin' },
        ]}
      />

      <Text style={{ fontWeight: '600', marginTop: spacing.lg, color: palette.textPrimary }}>Support</Text>
      <Pressable onPress={() => void Linking.openURL(`tel:${SUPPORT_PHONE}`)} style={{ marginTop: spacing.sm }}>
        <Text style={{ color: colors.primary }}>Phone: {SUPPORT_PHONE}</Text>
      </Pressable>
      <Pressable onPress={() => void Linking.openURL(`mailto:${SUPPORT_EMAIL}`)} style={{ marginTop: spacing.xs }}>
        <Text style={{ color: colors.primary }}>Email: {SUPPORT_EMAIL}</Text>
      </Pressable>
      <Pressable onPress={() => void Linking.openURL(WEBSITE)} style={{ marginTop: spacing.xs }}>
        <Text style={{ color: colors.primary }}>Website: {WEBSITE}</Text>
      </Pressable>

      <Pressable onPress={() => void Linking.openURL(`${WEBSITE}/privacy`)} style={{ marginTop: spacing.lg }}>
        <Text style={{ color: colors.primary, fontSize: fontSizes.sm }}>Privacy policy</Text>
      </Pressable>
      <Pressable onPress={() => void Linking.openURL(`${WEBSITE}/terms`)} style={{ marginTop: spacing.xs }}>
        <Text style={{ color: colors.primary, fontSize: fontSizes.sm }}>Terms of use</Text>
      </Pressable>
    </ScreenContainer>
  );
};
