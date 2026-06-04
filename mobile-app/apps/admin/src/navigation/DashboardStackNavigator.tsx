import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { ApprovalCenterScreen, ApprovalDetailScreen } from '../features/approvals';
import { DashboardScreen } from '../features/dashboard';
import type { DashboardStackParamList } from './dashboardStackTypes';

const Stack = createStackNavigator<DashboardStackParamList>();

export const DashboardStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="DashboardHome" component={DashboardScreen} />
    <Stack.Screen name="ApprovalCenter" component={ApprovalCenterScreen} />
    <Stack.Screen name="ApprovalDetail" component={ApprovalDetailScreen} />
  </Stack.Navigator>
);
