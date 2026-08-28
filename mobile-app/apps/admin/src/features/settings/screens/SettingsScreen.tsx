import { getNavArea, useCan, useSchoolSettings } from '@erp/core';
import {
  PlaceholderScreen,
  ScreenContainer,
  SettingsHubLayout,
  useTheme,
  type ThemeMode,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import type { SettingsStackParamList } from '../../../navigation/settingsStackTypes';
import { useSurfaceModeControl } from '../../../providers/AppThemeProvider';

const area = getNavArea('settings');

const THEME_OPTIONS: Array<{ id: ThemeMode; label: string }> = [
  { id: 'light', label: 'Light' },
  { id: 'dark', label: 'Dark' },
  { id: 'auto', label: 'Automatic' },
];

export const SettingsScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<SettingsStackParamList>>();
  const { spacing, palette, typography, radius, colors, themeMode, setThemeMode, isDark } = useTheme();
  const { surfaceMode, setSurfaceMode } = useSurfaceModeControl();
  const canView = useCan('settings.view');
  const schoolQuery = useSchoolSettings({ enabled: canView });

  const schoolName = schoolQuery.data?.school_name?.trim() || 'Settings';
  const schoolSubtitle =
    schoolQuery.data?.school_email?.trim() || 'Administration & configuration';

  const groups = useMemo(
    () => [
      {
        title: 'School configuration',
        rows: [
          {
            id: 'school',
            label: 'School',
            subtitle: 'Name, contact, branding',
            icon: 'business-outline' as const,
            tone: 'blue' as const,
            onPress: () => navigation.navigate('SettingsSchool'),
          },
          {
            id: 'academic',
            label: 'Academic',
            subtitle: 'Years, terms, classes',
            icon: 'calendar-outline' as const,
            tone: 'teal' as const,
            onPress: () => navigation.navigate('SettingsAcademic'),
          },
          {
            id: 'grading',
            label: 'Grading',
            subtitle: 'Schemes and exam types',
            icon: 'ribbon-outline' as const,
            tone: 'violet' as const,
            onPress: () => navigation.navigate('SettingsGrading'),
          },
          {
            id: 'roles',
            label: 'Roles',
            subtitle: 'Permissions (read-only)',
            icon: 'shield-checkmark-outline' as const,
            tone: 'indigo' as const,
            onPress: () => navigation.navigate('SettingsRoles'),
          },
        ],
      },
      {
        title: 'This device',
        rows: [
          {
            id: 'session',
            label: 'Session & security',
            subtitle: 'PIN, password, signed-in devices',
            icon: 'lock-closed-outline' as const,
            tone: 'cyan' as const,
            onPress: () => navigation.navigate('SettingsSession'),
          },
          {
            id: 'about',
            label: 'About & support',
            subtitle: 'Version, live updates, legal',
            icon: 'information-circle-outline' as const,
            tone: 'amber' as const,
            onPress: () => navigation.navigate('SettingsAbout'),
          },
        ],
      },
    ],
    [navigation],
  );

  if (!canView) {
    return (
      <PlaceholderScreen
        title={area.label}
        description="You need settings.view permission to open the settings hub."
        icon="lock-closed-outline"
      />
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ paddingBottom: spacing.md }}>
      <SettingsHubLayout
        schoolName={schoolName}
        schoolSubtitle={schoolSubtitle}
        meta="Read-only on mobile"
        appearance={
          <View style={{ gap: spacing.sm }}>
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.overline.fontSize,
                fontWeight: typography.overline.fontWeight,
                letterSpacing: typography.overline.letterSpacing,
                textTransform: 'uppercase',
                marginLeft: spacing.xs,
              }}
            >
              Appearance
            </Text>
            <View
              style={[
                styles.appearanceCard,
                {
                  backgroundColor: palette.surfaceRaised,
                  borderColor: palette.borderSubtle,
                  borderRadius: radius.card,
                  padding: spacing.md,
                },
              ]}
            >
              <View style={[styles.segment, { backgroundColor: palette.surfaceMuted, borderRadius: radius.control }]}>
                {THEME_OPTIONS.map((mode) => {
                  const active = themeMode === mode.id;
                  return (
                    <Pressable
                      key={mode.id}
                      onPress={() => setThemeMode(mode.id)}
                      accessibilityRole="button"
                      accessibilityState={{ selected: active }}
                      style={[
                        styles.segmentItem,
                        active
                          ? { backgroundColor: palette.surfaceRaised, borderColor: colors.primary }
                          : { borderColor: 'transparent' },
                      ]}
                    >
                      <Text
                        style={{
                          color: active ? colors.primary : palette.textSub,
                          fontWeight: active ? '700' : '600',
                          fontSize: typography.caption.fontSize,
                        }}
                      >
                        {mode.label}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
              <Text
                style={{
                  color: palette.textSub,
                  fontSize: typography.caption.fontSize,
                  marginTop: spacing.sm,
                }}
              >
                Automatic uses light during the day and dark from 7:00 pm to 6:00 am.
              </Text>
              <View style={[styles.amoledRow, { marginTop: spacing.md, opacity: isDark ? 1 : 0.45 }]}>
                <View style={{ flex: 1, marginRight: spacing.sm }}>
                  <Text style={{ color: palette.textMain, fontWeight: '600' }}>AMOLED black</Text>
                  <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                    True black in dark mode
                  </Text>
                </View>
                <Switch
                  value={surfaceMode === 'amoled'}
                  disabled={!isDark}
                  onValueChange={(on) => setSurfaceMode(on ? 'amoled' : 'default')}
                />
              </View>
            </View>
          </View>
        }
        groups={groups}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  appearanceCard: {
    borderWidth: StyleSheet.hairlineWidth,
  },
  segment: {
    flexDirection: 'row',
    padding: 4,
    gap: 4,
  },
  segmentItem: {
    flex: 1,
    minHeight: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 14,
    borderWidth: StyleSheet.hairlineWidth,
  },
  amoledRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
});
