import { getNavArea } from '@erp/core';
import {
  DashboardHero,
  DashboardSection,
  EmptyState,
  QuickAction,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

const area = getNavArea('people');

/** People hub — points into Staff Registry and related People stack screens. */
export const PeopleScreen: React.FC = () => {
  const { spacing } = useTheme();
  const navigation = useNavigation<StackNavigationProp<PeopleStackParamList>>();

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <DashboardHero
        variant="people"
        title={area.label}
        subtitle={area.description}
      />

      <DashboardSection title="Quick actions">
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <QuickAction
            label="Staff registry"
            icon="people-outline"
            onPress={() => navigation.navigate('StaffRegistry')}
          />
          <QuickAction
            label="Leave approvals"
            icon="calendar-outline"
            onPress={() => navigation.navigate('LeaveManagement')}
          />
          <QuickAction
            label="Leave types"
            icon="list-outline"
            onPress={() => navigation.navigate('LeaveTypes')}
          />
          <QuickAction
            label="Apply leave"
            icon="add-circle-outline"
            onPress={() => navigation.navigate('LeaveApply')}
          />
          <QuickAction
            label="Staff advances"
            icon="wallet-outline"
            onPress={() => navigation.navigate('StaffAdvances')}
          />
          <QuickAction
            label="Payroll"
            icon="cash-outline"
            onPress={() => navigation.navigate('PayrollRecords')}
          />
          <QuickAction
            label="Staff clock"
            icon="time-outline"
            onPress={() => navigation.navigate('StaffClock')}
          />
        </View>
      </DashboardSection>

      <EmptyState
        title="Open staff registry"
        message="Browse and manage staff profiles, leave, and HR records."
        icon="people-outline"
        actionLabel="Open staff registry"
        onAction={() => navigation.navigate('StaffRegistry')}
      />
    </ScreenContainer>
  );
};
