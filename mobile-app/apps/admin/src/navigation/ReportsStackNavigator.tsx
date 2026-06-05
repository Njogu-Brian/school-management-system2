import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { ReportsHubScreen } from '../features/reports';
import type { ReportsStackParamList } from './reportsStackTypes';

const Stack = createStackNavigator<ReportsStackParamList>();

export const ReportsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="ReportsHub" component={ReportsHubScreen} />
  </Stack.Navigator>
);
