import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  OperationsDashboardScreen,
  TripDetailScreen,
  TripsListScreen,
} from '../features/operations';
import type { OperationsStackParamList } from './operationsStackTypes';

const Stack = createStackNavigator<OperationsStackParamList>();

export const OperationsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="OperationsDashboard">
    <Stack.Screen name="OperationsDashboard" component={OperationsDashboardScreen} />
    <Stack.Screen name="TripsList" component={TripsListScreen} />
    <Stack.Screen name="TripDetail" component={TripDetailScreen} />
  </Stack.Navigator>
);
