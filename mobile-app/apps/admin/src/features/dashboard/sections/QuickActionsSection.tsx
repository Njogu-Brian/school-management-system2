import { useRbac } from '@erp/core';
import { DashboardSection, QuickAction } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { QUICK_ACTION_PLACEHOLDERS } from '../data/placeholders';

export const QuickActionsSection: React.FC = () => {
  const { can } = useRbac();
  const { spacing } = useTheme();

  const actions = useMemo(
    () =>
      QUICK_ACTION_PLACEHOLDERS.filter((a) => can(a.permissions)),
    [can],
  );

  if (actions.length === 0) {
    return null;
  }

  return (
    <DashboardSection title="Quick actions" subtitle="Jump to frequent tasks">
      <View style={[styles.row, { gap: spacing.sm }]}>
        {actions.map((action) => (
          <QuickAction
            key={action.id}
            label={action.label}
            icon={action.icon as keyof typeof Ionicons.glyphMap}
            onPress={() => undefined}
          />
        ))}
      </View>
    </DashboardSection>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
});
