import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  AssetDetailScreen,
  AssetsListScreen,
  InventoryListScreen,
  OperationsDashboardScreen,
  RequisitionDetailScreen,
  RequisitionsListScreen,
  TripDetailScreen,
  TripsListScreen,
  VisitorCheckInScreen,
  VisitorDetailScreen,
  VisitorsListScreen,
  TeacherTransportScreen,
  DriverTripsScreen,
  DriverTripDetailScreen,
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
    <Stack.Screen name="RequisitionDetail" component={RequisitionDetailScreen} />
    <Stack.Screen name="VisitorsList" component={VisitorsListScreen} />
    <Stack.Screen name="VisitorDetail" component={VisitorDetailScreen} />
    <Stack.Screen name="VisitorCheckIn" component={VisitorCheckInScreen} />
    <Stack.Screen name="AssetsList" component={AssetsListScreen} />
    <Stack.Screen name="AssetDetail" component={AssetDetailScreen} />
    <Stack.Screen name="TeacherTransport" component={TeacherTransportScreen} />
    <Stack.Screen name="DriverTrips" component={DriverTripsScreen} />
    <Stack.Screen name="DriverTripDetail" component={DriverTripDetailScreen} />
  </Stack.Navigator>
);
