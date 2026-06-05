import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  BoardPackScreen,
  ExpenseReportsScreen,
  ReportsHubScreen,
  WeeklyReportDetailScreen,
  WeeklyReportsListScreen,
} from '../features/reports';
import type { ReportsStackParamList } from './reportsStackTypes';

const Stack = createStackNavigator<ReportsStackParamList>();

export const ReportsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="ReportsHub" component={ReportsHubScreen} />
    <Stack.Screen name="BoardPack" component={BoardPackScreen} />
    <Stack.Screen name="ExpenseReports" component={ExpenseReportsScreen} />
    <Stack.Screen name="WeeklyReportsList" component={WeeklyReportsListScreen} />
    <Stack.Screen name="WeeklyReportDetail" component={WeeklyReportDetailScreen} />
  </Stack.Navigator>
);
