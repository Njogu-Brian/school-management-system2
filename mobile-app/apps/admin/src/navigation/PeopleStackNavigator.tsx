import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  PeopleScreen,
  PerformanceReviewDetailScreen,
  StaffDetailScreen,
  StaffRegistryScreen,
  TrainingRecordDetailScreen,
  StaffClockScreen,
  StaffClockTeamScreen,
  StaffEditScreen,
  LeaveApplyScreen,
  LeaveManagementScreen,
  LeaveTypesScreen,
  StaffAdvancesScreen,
  PayrollRecordsScreen,
  PayrollDetailScreen,
} from '../features/people';
import type { PeopleStackParamList } from './peopleStackTypes';

const Stack = createStackNavigator<PeopleStackParamList>();

/** People workspace: hub + staff registry + Staff 360 + leave/payroll/clock. */
export const PeopleStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="PeopleHub">
    <Stack.Screen name="PeopleHub" component={PeopleScreen} />
    <Stack.Screen name="StaffRegistry" component={StaffRegistryScreen} />
    <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
    <Stack.Screen name="PerformanceReviewDetail" component={PerformanceReviewDetailScreen} />
    <Stack.Screen name="TrainingRecordDetail" component={TrainingRecordDetailScreen} />
    <Stack.Screen name="StaffClock" component={StaffClockScreen} />
    <Stack.Screen name="StaffClockTeam" component={StaffClockTeamScreen} />
    <Stack.Screen name="StaffEdit" component={StaffEditScreen} />
    <Stack.Screen name="LeaveApply" component={LeaveApplyScreen} />
    <Stack.Screen name="LeaveManagement" component={LeaveManagementScreen} />
    <Stack.Screen name="LeaveTypes" component={LeaveTypesScreen} />
    <Stack.Screen name="StaffAdvances" component={StaffAdvancesScreen} />
    <Stack.Screen name="PayrollRecords" component={PayrollRecordsScreen} />
    <Stack.Screen name="PayrollDetail" component={PayrollDetailScreen} />
  </Stack.Navigator>
);
