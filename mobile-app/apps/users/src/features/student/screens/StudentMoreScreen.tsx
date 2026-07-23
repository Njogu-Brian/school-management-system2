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
import type { StudentStackParamList } from '../../../navigation/student/studentStackTypes';

type Nav = StackNavigationProp<StudentStackParamList>;

const LINKS: Array<{
  label: string;
  subtitle: string;
  icon: 'person-outline' | 'settings-outline' | 'notifications-outline' | 'alert-circle-outline';
  tone: 'cyan' | 'indigo' | 'blue' | 'rose';
  route: keyof StudentStackParamList;
}> = [
  {
    label: 'My profile',
    subtitle: 'View and edit your account',
    icon: 'person-outline',
    tone: 'cyan',
    route: 'MyProfile',
  },
  {
    label: 'Notifications',
    subtitle: 'Alerts and reminders',
    icon: 'notifications-outline',
    tone: 'blue',
    route: 'Notifications',
  },
  {
    label: 'Raise a concern',
    subtitle: 'Tell the school about an issue',
    icon: 'alert-circle-outline',
    tone: 'rose',
    route: 'RaiseConcern',
  },
  {
    label: 'My concerns',
    subtitle: 'Track concerns you have raised',
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

export const StudentMoreScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="More" onProfilePress={() => navigation.navigate('MyProfile')} />
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
