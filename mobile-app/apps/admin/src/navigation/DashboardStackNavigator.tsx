import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { ActivityCenterScreen, AuditDetailScreen } from '../features/activity';
import { ApprovalCenterScreen, ApprovalDetailScreen } from '../features/approvals';
import { DashboardScreen } from '../features/dashboard';
import { NotificationsListScreen, NotificationDetailScreen } from '../features/notifications';
import { GlobalSearchScreen } from '../features/search';
import { UserProfileScreen } from '../features/profile/screens/UserProfileScreen';
import type { DashboardStackParamList } from './dashboardStackTypes';

const Stack = createStackNavigator<DashboardStackParamList>();

export const DashboardStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="DashboardHome" component={DashboardScreen} />
    <Stack.Screen name="ApprovalCenter" component={ApprovalCenterScreen} />
    <Stack.Screen name="ApprovalDetail" component={ApprovalDetailScreen} />
    <Stack.Screen name="NotificationsList" component={NotificationsListScreen} />
    <Stack.Screen name="NotificationDetail" component={NotificationDetailScreen} />
    <Stack.Screen name="GlobalSearch" component={GlobalSearchScreen} />
    <Stack.Screen name="ActivityCenter" component={ActivityCenterScreen} />
    <Stack.Screen name="AuditDetail" component={AuditDetailScreen} />
    <Stack.Screen name="UserProfile" component={UserProfileScreen} />
  </Stack.Navigator>
);
