import { useRbac } from '@erp/core';
import { DashboardSection, QuickAction } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import { useTheme } from '@erp/ui';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { QUICK_ACTION_PLACEHOLDERS } from '../data/placeholders';

export const QuickActionsSection: React.FC = () => {
  const { can } = useRbac();
  const { spacing } = useTheme();
  const navigation = useNavigation<StackNavigationProp<DashboardStackParamList>>();

  const actions = useMemo(
    () =>
      QUICK_ACTION_PLACEHOLDERS.filter((a) => can(a.permissions)),
    [can],
  );

  const onActionPress = useCallback(
    (actionId: string) => {
      if (actionId === 'qa_approvals') {
        navigation.navigate('ApprovalCenter');
      }
    },
    [navigation],
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
            onPress={() => onActionPress(action.id)}
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
