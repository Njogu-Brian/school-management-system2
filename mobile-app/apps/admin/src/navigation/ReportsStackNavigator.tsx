import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  BalanceSheetScreen,
  BoardPackScreen,
  ExecutiveAnalyticsScreen,
  ExpenseDetailScreen,
  ExpenseReportsScreen,
  ExpensesListScreen,
  IncomeStatementScreen,
  LedgerScreen,
  ReportsHubScreen,
  WeeklyReportDetailScreen,
  WeeklyReportsListScreen,
} from '../features/reports';
import type { ReportsStackParamList } from './reportsStackTypes';

const Stack = createStackNavigator<ReportsStackParamList>();

export const ReportsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="ReportsHub" component={ReportsHubScreen} />
    <Stack.Screen name="ExecutiveAnalytics" component={ExecutiveAnalyticsScreen} />
    <Stack.Screen name="BoardPack" component={BoardPackScreen} />
    <Stack.Screen name="ExpenseReports" component={ExpenseReportsScreen} />
    <Stack.Screen name="ExpensesList" component={ExpensesListScreen} />
    <Stack.Screen name="ExpenseDetail" component={ExpenseDetailScreen} />
    <Stack.Screen name="IncomeStatement" component={IncomeStatementScreen} />
    <Stack.Screen name="BalanceSheet" component={BalanceSheetScreen} />
    <Stack.Screen name="Ledger" component={LedgerScreen} />
    <Stack.Screen name="WeeklyReportsList" component={WeeklyReportsListScreen} />
    <Stack.Screen name="WeeklyReportDetail" component={WeeklyReportDetailScreen} />
  </Stack.Navigator>
);
