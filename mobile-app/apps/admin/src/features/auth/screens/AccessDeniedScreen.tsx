import { useAuth, useCurrentUser } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Linking, StyleSheet, Text, View } from 'react-native';

/**
 * Shown when a user authenticates successfully but their role is not an Admin App role
 * (e.g. a Teacher signing into the Admin Console). The session is valid; access to this
 * binary is denied. The symmetric case (Admin → Staff App) is enforced by the Staff App.
 */
export const AccessDeniedScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { palette, colors, fontSizes, spacing } = useTheme();

  const roleLabel = user?.roleName ?? 'Your account';

  const openStaffApp = (): void => {
    // Best-effort deep link into the Staff App; falls back silently if not installed.
    void Linking.openURL('schoolerpstaff://').catch(() => undefined);
  };

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <View style={[styles.iconWrap, { backgroundColor: `${colors.warning}1a` }]}>
        <Ionicons name="lock-closed" size={36} color={colors.warning} />
      </View>
      <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
        Access denied
      </Text>
      <Text style={[styles.body, { color: palette.textSecondary, fontSize: fontSizes.md }]}>
        {roleLabel} doesn’t have access to the Admin Console. This app is for school
        administrators. Please use the Staff App instead.
      </Text>

      <View style={{ marginTop: spacing.xl, alignSelf: 'stretch' }}>
        <Button label="Open Staff App" variant="secondary" onPress={openStaffApp} />
        <Button
          label="Sign in with a different account"
          variant="ghost"
          onPress={logout}
          style={{ marginTop: spacing.md }}
        />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrap: {
    width: 84,
    height: 84,
    borderRadius: 42,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  title: { fontWeight: '700', marginBottom: 12, textAlign: 'center' },
  body: { textAlign: 'center', lineHeight: 22 },
});
