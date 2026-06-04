import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { StaffDetailScreen, StaffRegistryScreen } from '../features/people';
import type { PeopleStackParamList } from './peopleStackTypes';

const Stack = createStackNavigator<PeopleStackParamList>();

/** People workspace: staff registry + detail routing (Staff 360 tabs in later sprints). */
export const PeopleStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StaffRegistry" component={StaffRegistryScreen} />
    <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
  </Stack.Navigator>
);
