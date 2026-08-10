import {
  useAuth,
} from '@erp/core';
import {
  Button,
  ScreenContainer,
  SearchBar,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { confirmAction } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<ParentStackParamList>;

const LINKS: Array<{
  label: string;
  subtitle: string;
  icon: 'person-outline' | 'wallet-outline' | 'megaphone-outline' | 'notifications-outline' | 'settings-outline' | 'alert-circle-outline';
  glyph?: 'wallet' | 'person' | 'megaphone' | 'notifications' | 'settings' | 'generic';
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
  const { palette, spacing, typography, radius, colors } = useTheme();
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
        <Pressable
          key={item.route}
          onPress={() => navigation.navigate(item.route as never)}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: spacing.md,
            marginBottom: spacing.sm,
            padding: spacing.md,
            borderRadius: radius.lg,
            borderWidth: 1,
            borderColor: palette.border,
            backgroundColor: palette.surface,
          }}
        >
          <Soft3DIcon name={item.icon} glyph={item.glyph} tone={item.tone} size={44} />
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.label}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {item.subtitle}
            </Text>
          </View>
        </Pressable>
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
