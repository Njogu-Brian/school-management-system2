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
            icon="staff-registry"
            onPress={() => navigation.navigate('StaffRegistry')}
          />
          <QuickAction
            label="Leave approvals"
            icon="leave-approvals"
            onPress={() => navigation.navigate('LeaveManagement')}
          />
          <QuickAction
            label="Leave types"
            icon="leave-types"
            onPress={() => navigation.navigate('LeaveTypes')}
          />
          <QuickAction
            label="Apply leave"
            icon="apply-leave"
            onPress={() => navigation.navigate('LeaveApply')}
          />
          <QuickAction
            label="Staff advances"
            icon="staff-advances"
            onPress={() => navigation.navigate('StaffAdvances')}
          />
          <QuickAction
            label="Payroll"
            icon="payroll"
            onPress={() => navigation.navigate('PayrollRecords')}
          />
          <QuickAction
            label="Staff attendance"
            icon="staff-attendance"
            onPress={() => navigation.navigate('StaffClock')}
          />
          <QuickAction
            label="Staff calendar"
            icon="calendar"
            onPress={() => navigation.navigate('StaffAttendanceCalendar')}
          />
          <QuickAction
            label="Require password change"
            icon="require-password-change"
            onPress={() => navigation.navigate('ForcePasswordChange')}
          />
        </View>
      </DashboardSection>

      <View style={{ alignItems: 'center', marginTop: spacing.lg }}>
        <EmptyState
          title="Open staff registry"
          message="Browse and manage staff profiles, leave, and HR records."
          icon="people-outline"
          actionLabel="Open staff registry"
          onAction={() => navigation.navigate('StaffRegistry')}
        />
      </View>
    </ScreenContainer>
  );
};
