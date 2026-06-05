import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { AdmissionsWorkspaceScreen, ApplicationDetailScreen } from '../features/admissions';
import type { AdmissionsStackParamList } from './admissionsStackTypes';

const Stack = createStackNavigator<AdmissionsStackParamList>();

export const AdmissionsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="AdmissionsWorkspace" component={AdmissionsWorkspaceScreen} />
    <Stack.Screen name="ApplicationDetail" component={ApplicationDetailScreen} />
  </Stack.Navigator>
);
