import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  InventoryListScreen,
  OperationsDashboardScreen,
  RequisitionsListScreen,
  TripDetailScreen,
  TripsListScreen,
  VisitorsListScreen,
} from '../features/operations';
import type { OperationsStackParamList } from './operationsStackTypes';

const Stack = createStackNavigator<OperationsStackParamList>();

export const OperationsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="OperationsDashboard">
    <Stack.Screen name="OperationsDashboard" component={OperationsDashboardScreen} />
    <Stack.Screen name="TripsList" component={TripsListScreen} />
    <Stack.Screen name="TripDetail" component={TripDetailScreen} />
    <Stack.Screen name="InventoryList" component={InventoryListScreen} />
    <Stack.Screen name="RequisitionsList" component={RequisitionsListScreen} />
    <Stack.Screen name="VisitorsList" component={VisitorsListScreen} />
  </Stack.Navigator>
);
