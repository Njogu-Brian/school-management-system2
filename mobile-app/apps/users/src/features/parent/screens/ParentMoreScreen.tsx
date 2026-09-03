import {
  useAuth,
} from '@erp/core';
import { Button, ListRowCard, ScreenContainer, SearchBar, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';
import { confirmAction } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<ParentStackParamList>;

const LINKS: Array<{
  label: string;
  subtitle: string;
  icon:
    | 'person-outline'
    | 'wallet-outline'
    | 'megaphone-outline'
    | 'notifications-outline'
    | 'settings-outline'
    | 'alert-circle-outline'
    | 'chatbubbles-outline'
    | 'sparkles-outline';
  glyph?: 'wallet' | 'person' | 'megaphone' | 'notifications' | 'settings' | 'activities' | 'generic';
  tone: 'cyan' | 'amber' | 'blue' | 'indigo' | 'rose' | 'emerald';
  route: keyof ParentStackParamList;
}> = [
  {
    label: 'My profile',
    subtitle: 'View and edit your account',
    icon: 'person-outline',
    tone: 'cyan',
    route: 'MyProfile',
  },
  {
    label: 'Co-curricular',
    subtitle: 'Clubs, ballet, skating, music & yoghurt',
    icon: 'sparkles-outline',
    glyph: 'activities',
    tone: 'amber',
    route: 'CoCurricularHub',
  },
  {
    label: 'Diary',
    subtitle: 'Messages with teachers',
    icon: 'chatbubbles-outline',
    tone: 'blue',
    route: 'DiaryList',
  },
  {
    label: 'Wallets',
    subtitle: 'Balance, top up & saving plans',
    icon: 'wallet-outline',
    glyph: 'wallet',
    tone: 'emerald',
    route: 'WalletHome',
  },
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
  const { logout } = useAuth();
  const { spacing, colors } = useTheme();
  const [query, setQuery] = useState('');

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return LINKS;
    return LINKS.filter(
      (l) => l.label.toLowerCase().includes(q) || l.subtitle.toLowerCase().includes(q),
    );
  }, [query]);

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <View style={{ marginBottom: spacing.md }}>
        <AppModeSwitch />
      </View>
      <SearchBar
        expandable
        value={query}
        onChangeText={setQuery}
        placeholder="Search menu…"
        actionLabel="Go"
        onActionPress={() => {
          if (filtered[0]) navigation.navigate(filtered[0].route as never);
        }}
      />
      {filtered.map((item) => (
        <ListRowCard
          key={item.route}
          title={item.label}
          subtitle={item.subtitle}
          icon={item.icon}
          glyph={item.glyph}
          tone={item.tone}
          onPress={() => navigation.navigate(item.route as never)}
        />
      ))}
      <Button
        label="Sign out"
        variant="ghost"
        onPress={() =>
          confirmAction('Sign out', 'Sign out of the Users app on this device?', 'Sign out', () => void logout(), true)
        }
        style={{ marginTop: spacing.md, borderColor: colors.error, borderWidth: 1 }}
      />
    </ScreenContainer>
  );
};
