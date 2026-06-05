import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  BillingListScreen,
  CollectionsScreen,
  FinanceDashboardScreen,
  InvoiceDetailScreen,
  PaymentDetailScreen,
  ReconciliationScreen,
  StatementsScreen,
  TransactionDetailScreen,
} from '../features/finance';
import type { FinanceStackParamList } from './financeStackTypes';

const Stack = createStackNavigator<FinanceStackParamList>();

export const FinanceStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="FinanceDashboard">
    <Stack.Screen name="FinanceDashboard" component={FinanceDashboardScreen} />
    <Stack.Screen name="BillingList" component={BillingListScreen} />
    <Stack.Screen name="InvoiceDetail" component={InvoiceDetailScreen} />
    <Stack.Screen name="CollectionsList" component={CollectionsScreen} />
    <Stack.Screen name="PaymentDetail" component={PaymentDetailScreen} />
    <Stack.Screen name="Statements" component={StatementsScreen} />
    <Stack.Screen name="ReconciliationList" component={ReconciliationScreen} />
    <Stack.Screen name="TransactionDetail" component={TransactionDetailScreen} />
  </Stack.Navigator>
);
