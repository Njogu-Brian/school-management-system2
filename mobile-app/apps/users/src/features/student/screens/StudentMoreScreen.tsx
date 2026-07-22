import {
  AcademicScreenHeader,
  ScreenContainer,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { Pressable, Text, View } from 'react-native';

const LINKS = [
  {
    label: 'Settings',
    subtitle: 'Theme and account',
    icon: 'settings-outline' as const,
    tone: 'indigo' as const,
    route: 'Settings',
  },
  {
    label: 'Notifications',
    subtitle: 'Alerts and reminders',
    icon: 'notifications-outline' as const,
    tone: 'blue' as const,
    route: 'Notifications',
  },
];

export const StudentMoreScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="More" />
      {LINKS.map((item) => (
        <Pressable
          key={item.route}
          onPress={() => (navigation as { navigate: (n: string) => void }).navigate(item.route)}
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
