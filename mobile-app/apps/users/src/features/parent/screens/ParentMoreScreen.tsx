import {
  AcademicScreenHeader,
  ScreenContainer,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;

const LINKS: Array<{
  label: string;
  subtitle: string;
  icon: 'megaphone-outline' | 'notifications-outline' | 'settings-outline' | 'alert-circle-outline';
  tone: 'amber' | 'blue' | 'indigo' | 'rose';
  route: keyof ParentStackParamList;
}> = [
  {
    label: 'Announcements',
    subtitle: 'School notices and updates',
    icon: 'megaphone-outline',
    tone: 'amber',
    route: 'Announcements',
  },
  {
    label: 'Notifications',
    subtitle: 'Alerts and reminders',
    icon: 'notifications-outline',
    tone: 'blue',
    route: 'Notifications',
  },
  {
    label: 'Concerns',
    subtitle: 'View or raise concerns',
    icon: 'alert-circle-outline',
    tone: 'rose',
    route: 'ConcernsList',
  },
  {
    label: 'Settings',
    subtitle: 'Theme and account',
    icon: 'settings-outline',
    tone: 'indigo',
    route: 'Settings',
  },
];

export const ParentMoreScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="More" />
      {LINKS.map((item) => (
        <Pressable
          key={item.route}
          onPress={() => navigation.navigate(item.route as never)}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: spacing.md,
            backgroundColor: palette.surface,
            borderWidth: 1,
            borderColor: palette.border,
            borderRadius: radius.lg,
            padding: spacing.md,
            marginBottom: spacing.sm,
          }}
        >
          <Soft3DIcon name={item.icon} tone={item.tone} size={44} />
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.label}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {item.subtitle}
            </Text>
          </View>
        </Pressable>
      ))}
    </ScreenContainer>
  );
};
