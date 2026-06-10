import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  AnnouncementDetailScreen,
  AnnouncementFormScreen,
  AnnouncementsListScreen,
  CommunicationDashboardScreen,
  SmsComposeScreen,
  SmsHistoryScreen,
  SmsLogDetailScreen,
  TemplateDetailScreen,
  TemplateFormScreen,
  TemplatesListScreen,
} from '../features/communication';
import type { CommunicationStackParamList } from './communicationStackTypes';

const Stack = createStackNavigator<CommunicationStackParamList>();

export const CommunicationStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="CommunicationDashboard">
    <Stack.Screen name="CommunicationDashboard" component={CommunicationDashboardScreen} />
    <Stack.Screen name="AnnouncementsList" component={AnnouncementsListScreen} />
    <Stack.Screen name="AnnouncementDetail" component={AnnouncementDetailScreen} />
    <Stack.Screen name="AnnouncementForm" component={AnnouncementFormScreen} />
    <Stack.Screen name="SmsCompose" component={SmsComposeScreen} />
    <Stack.Screen name="SmsHistory" component={SmsHistoryScreen} />
    <Stack.Screen name="SmsLogDetail" component={SmsLogDetailScreen} />
    <Stack.Screen name="TemplatesList" component={TemplatesListScreen} />
    <Stack.Screen name="TemplateForm" component={TemplateFormScreen} />
    <Stack.Screen name="TemplateDetail" component={TemplateDetailScreen} />
  </Stack.Navigator>
);
