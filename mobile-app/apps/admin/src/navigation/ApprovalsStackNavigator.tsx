import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { ApprovalDetailScreen, ApprovalsWorkspaceScreen } from '../features/approvals';
import type { ApprovalsStackParamList } from './approvalsStackTypes';

const Stack = createStackNavigator<ApprovalsStackParamList>();

export const ApprovalsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="ApprovalsHome" component={ApprovalsWorkspaceScreen} />
    <Stack.Screen name="ApprovalDetail" component={ApprovalDetailScreen} />
  </Stack.Navigator>
);
