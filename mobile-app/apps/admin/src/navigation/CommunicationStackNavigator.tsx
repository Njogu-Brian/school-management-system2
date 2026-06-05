import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { AnnouncementsListScreen, CommunicationDashboardScreen } from '../features/communication';
import type { CommunicationStackParamList } from './communicationStackTypes';

const Stack = createStackNavigator<CommunicationStackParamList>();

export const CommunicationStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="CommunicationDashboard">
    <Stack.Screen name="CommunicationDashboard" component={CommunicationDashboardScreen} />
    <Stack.Screen name="AnnouncementsList" component={AnnouncementsListScreen} />
  </Stack.Navigator>
);
