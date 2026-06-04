import { useRolesSettings } from '@erp/core';
import { SettingCard, SettingsSectionHeader } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export const RolesSettingsSection: React.FC = () => {
  const { colors, spacing, fontSizes, palette } = useTheme();
  const query = useRolesSettings();
  const [expandedRoleId, setExpandedRoleId] = useState<number | null>(null);

  const roleCards = useMemo(() => query.data ?? [], [query.data]);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <View>
        <Text style={{ color: colors.error }}>Could not load roles.</Text>
        <Pressable onPress={() => void query.refetch()} style={{ marginTop: spacing.sm }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <View>
      <SettingsSectionHeader
        title="Roles & permissions"
        subtitle="Read-only view of roles and assigned permissions. Edit on web portal."
      />
      <SettingCard id="roles-count" label="Roles" value={String(roleCards.length)} />
      {roleCards.map((role) => {
        const expanded = expandedRoleId === role.id;
        const preview = role.permissions.slice(0, 4).join(', ');
        const more = role.permissions.length > 4 ? ` +${role.permissions.length - 4} more` : '';
        return (
          <Pressable key={role.id} onPress={() => setExpandedRoleId(expanded ? null : role.id)}>
            <SettingCard
              id={`role-${role.id}`}
              label={role.name}
              value={`${role.permissions_count} permission(s)`}
              hint={expanded ? role.permissions.join(' · ') : preview + more}
            />
          </Pressable>
        );
      })}
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.sm }}>
        Permission changes are not available in the mobile app.
      </Text>
    </View>
  );
};
