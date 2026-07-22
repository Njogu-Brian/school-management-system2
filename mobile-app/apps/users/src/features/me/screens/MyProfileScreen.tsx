import { useAuth, useCurrentUser } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';

export const MyProfileScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="My profile" onBack={() => navigation.goBack()} />
      <View
        style={{
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderWidth: 1,
          borderRadius: radius.lg,
          padding: spacing.lg,
        }}
      >
        <Text style={{ color: palette.textPrimary, fontSize: typography.headline.fontSize, fontWeight: '700' }}>
          {user?.name ?? '—'}
        </Text>
        <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>{user?.roleName ?? user?.role}</Text>
        {user?.email ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.xs }}>{user.email}</Text>
        ) : null}
        {user?.phone ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.xs }}>{user.phone}</Text>
        ) : null}
        {user?.staffId != null ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.sm }}>Staff ID: {user.staffId}</Text>
        ) : null}
      </View>
      <Button label="Sign out" variant="ghost" onPress={logout} style={{ marginTop: spacing.lg }} />
    </ScreenContainer>
  );
};
