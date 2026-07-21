import { useSchoolSettings } from '@erp/core';
import { EmptyState, SettingCard, SettingsSectionHeader, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, View } from 'react-native';

export const SchoolSettingsSection: React.FC = () => {
  const { colors, spacing } = useTheme();
  const query = useSchoolSettings();

  const cards = useMemo(() => {
    const s = query.data;
    if (!s) return [];
    return [
      { id: 'name', label: 'School name', value: s.school_name },
      { id: 'email', label: 'Email', value: s.school_email ?? '—' },
      { id: 'phone', label: 'Phone', value: s.school_phone ?? '—' },
      { id: 'address', label: 'Address', value: s.school_address ?? '—' },
      { id: 'tz', label: 'Timezone', value: s.timezone },
      { id: 'currency', label: 'Currency', value: s.currency },
      {
        id: 'primary',
        label: 'Brand primary',
        value: s.colors?.primary ?? '—',
        hint: 'Read-only on mobile',
      },
      {
        id: 'modules',
        label: 'Enabled modules',
        value:
          s.enabled_modules?.length > 0 ? s.enabled_modules.join(', ') : 'None configured',
      },
      { id: 'version', label: 'System version', value: s.system_version ?? '—' },
    ];
  }, [query.data]);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Could not load school settings"
        message={(query.error as Error)?.message ?? 'Try again in a moment.'}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }

  return (
    <View style={{ gap: spacing.sm }}>
      <SettingsSectionHeader
        title="School"
        subtitle="Identity, branding, and regional defaults (read-only on mobile)."
      />
      {cards.map((c) => (
        <SettingCard key={c.id} id={c.id} label={c.label} value={c.value} hint={c.hint} />
      ))}
    </View>
  );
};
