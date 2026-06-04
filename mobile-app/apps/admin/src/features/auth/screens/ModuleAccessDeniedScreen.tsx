import type { AdminAreaKey } from '@erp/core';
import { getNavArea } from '@erp/core';
import { useAuth } from '@erp/core';
import { Button, ScreenContainer, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';

export interface ModuleAccessDeniedScreenProps {
  areaKey?: AdminAreaKey;
}

/**
 * Shown when the user navigates to a module they lack permission for
 * (route-level protection, Batch 3).
 */
export const ModuleAccessDeniedScreen: React.FC<ModuleAccessDeniedScreenProps> = ({
  areaKey,
}) => {
  const { logout } = useAuth();
  const { palette, colors, fontSizes, spacing } = useTheme();
  const moduleLabel = areaKey ? getNavArea(areaKey).label : 'this module';

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <View style={[styles.iconWrap, { backgroundColor: `${colors.warning}1a` }]}>
        <Ionicons name="lock-closed" size={36} color={colors.warning} />
      </View>
      <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
        Access denied
      </Text>
      <Text style={[styles.body, { color: palette.textSecondary, fontSize: fontSizes.md }]}>
        You don&apos;t have permission to open {moduleLabel}. Contact your administrator if
        you need access.
      </Text>
      <Button
        label="Sign out"
        variant="ghost"
        onPress={logout}
        style={{ marginTop: spacing.xl }}
      />
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
