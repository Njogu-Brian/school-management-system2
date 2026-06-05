import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  PerformanceReviewDetailScreen,
  StaffDetailScreen,
  StaffRegistryScreen,
  TrainingRecordDetailScreen,
  StaffClockScreen,
  StaffClockTeamScreen,
} from '../features/people';
import type { PeopleStackParamList } from './peopleStackTypes';

const Stack = createStackNavigator<PeopleStackParamList>();

/** People workspace: staff registry + Staff 360 read-only profile. */
export const PeopleStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StaffRegistry" component={StaffRegistryScreen} />
    <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
    <Stack.Screen name="PerformanceReviewDetail" component={PerformanceReviewDetailScreen} />
    <Stack.Screen name="TrainingRecordDetail" component={TrainingRecordDetailScreen} />
    <Stack.Screen name="StaffClock" component={StaffClockScreen} />
    <Stack.Screen name="StaffClockTeam" component={StaffClockTeamScreen} />
  </Stack.Navigator>
);
