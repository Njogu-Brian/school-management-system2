import { Ionicons } from '@expo/vector-icons';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import Constants from 'expo-constants';
import React from 'react';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

const SUPPORT_PHONE = '0719396233';
const SUPPORT_EMAIL = 'info@royalkingsschools.sc.ke';
const WEBSITE = 'https://royalkingsschools.sc.ke';

export interface AboutScreenProps {
  onBack?: () => void;
}

type LinkRow = {
  id: string;
  label: string;
  value: string;
  icon: keyof typeof Ionicons.glyphMap;
  url: string;
};

export const AboutScreen: React.FC<AboutScreenProps> = ({ onBack }) => {
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const version = Constants.expoConfig?.version ?? '1.0.0';
  const build = Constants.expoConfig?.android?.versionCode ?? Constants.nativeBuildVersion ?? '—';

  const supportRows: LinkRow[] = [
    {
      id: 'phone',
      label: 'Phone',
      value: SUPPORT_PHONE,
      icon: 'call-outline',
      url: `tel:${SUPPORT_PHONE}`,
    },
    {
      id: 'email',
      label: 'Email',
      value: SUPPORT_EMAIL,
      icon: 'mail-outline',
      url: `mailto:${SUPPORT_EMAIL}`,
    },
    {
      id: 'website',
      label: 'Website',
      value: WEBSITE.replace(/^https?:\/\//, ''),
      icon: 'globe-outline',
      url: WEBSITE,
    },
  ];

  const legalRows: LinkRow[] = [
    {
      id: 'privacy',
      label: 'Privacy policy',
      value: 'View',
      icon: 'shield-checkmark-outline',
      url: `${WEBSITE}/privacy`,
    },
    {
      id: 'terms',
      label: 'Terms of use',
      value: 'View',
      icon: 'document-text-outline',
      url: `${WEBSITE}/terms`,
    },
  ];

  const renderLinkRow = (row: LinkRow) => (
    <Pressable
      key={row.id}
      onPress={() => void Linking.openURL(row.url)}
      accessibilityRole="link"
      style={({ pressed }) => [
        styles.row,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          paddingHorizontal: spacing.md,
          paddingVertical: spacing.sm,
          minHeight: 48,
          marginTop: spacing.sm,
          opacity: pressed ? 0.92 : 1,
        },
      ]}
    >
      <View
        style={[
          styles.iconWrap,
          { backgroundColor: palette.surfaceMuted, borderRadius: radius.sm },
        ]}
      >
        <Ionicons name={row.icon} size={20} color={colors.primary} />
      </View>
      <View style={{ flex: 1, gap: 2 }}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.body.fontSize,
            fontWeight: '600',
          }}
        >
          {row.label}
        </Text>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
          }}
          numberOfLines={1}
        >
          {row.value}
        </Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
    </Pressable>
  );

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      {onBack ? <AcademicScreenHeader title="About" onBack={onBack} /> : null}
      <Text
        style={{
          fontWeight: '700',
          fontSize: typography.title.fontSize,
          lineHeight: typography.title.lineHeight,
          color: palette.textPrimary,
        }}
      >
        Royal Kings ERP Admin
      </Text>
      <Text
        style={{
          color: palette.textSecondary,
          marginTop: spacing.xs,
          fontSize: typography.body.fontSize,
          lineHeight: typography.body.lineHeight,
        }}
      >
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

      <Text
        style={{
          fontWeight: '700',
          marginTop: spacing.lg,
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
        }}
      >
        Support
      </Text>
      {supportRows.map(renderLinkRow)}

      <Text
        style={{
          fontWeight: '700',
          marginTop: spacing.lg,
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
        }}
      >
        Legal
      </Text>
      {legalRows.map(renderLinkRow)}

      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.caption.fontSize,
          textAlign: 'center',
          marginTop: spacing.xl,
        }}
      >
        Version {version}
      </Text>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: {
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  iconWrap: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
