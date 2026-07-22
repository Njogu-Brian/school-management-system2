import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '@erp/ui';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { ActiveTripScreen } from '../../features/driver/screens/ActiveTripScreen';
import { BoardingChecklistScreen } from '../../features/driver/screens/BoardingChecklistScreen';
import {
  DriverHomeScreen,
  DriverMoreHubScreen,
  DriverRoutesScreen,
  DriverTripDetailScreen,
} from '../../features/driver/screens/DriverScreens';
import { DriverVehicleScreen } from '../../features/driver/screens/DriverVehicleScreen';
import { LeaveApplyScreen } from '../../features/me/screens/LeaveApplyScreen';
import { MyAdvancesScreen } from '../../features/me/screens/MyAdvancesScreen';
import { MyLeaveListScreen } from '../../features/me/screens/MyLeaveListScreen';
import { MyPayslipsScreen } from '../../features/me/screens/MyPayslipsScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { StaffClockScreen } from '../../features/me/screens/StaffClockScreen';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { getDefaultTabScreenOptions } from '../tabBarConfig';
import type { DriverStackParamList } from './driverStackTypes';

const Tab = createBottomTabNavigator();
const HomeStack = createStackNavigator<DriverStackParamList>();
const RoutesStack = createStackNavigator<DriverStackParamList>();
const AccountStack = createStackNavigator<DriverStackParamList>();

const driverSharedScreens = (Stack: typeof HomeStack) => (
  <>
    <Stack.Screen name="TripDetail" component={DriverTripDetailScreen} />
    <Stack.Screen name="BoardingChecklist" component={BoardingChecklistScreen} />
    <Stack.Screen name="ActiveTrip" component={ActiveTripScreen} />
    <Stack.Screen name="DriverVehicle" component={DriverVehicleScreen} />
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
    <Stack.Screen name="StaffClock" component={StaffClockScreen} />
    <Stack.Screen name="LeaveApply" component={LeaveApplyScreen} />
    <Stack.Screen name="MyLeaveList" component={MyLeaveListScreen} />
    <Stack.Screen name="MyPayslips" component={MyPayslipsScreen} />
    <Stack.Screen name="MyAdvances" component={MyAdvancesScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
    <Stack.Screen name="DriverSettings" component={SettingsScreen} />
  </>
);

const DriverHomeStack = () => (
  <HomeStack.Navigator screenOptions={{ headerShown: false }}>
    <HomeStack.Screen name="DriverHomeMain" component={DriverHomeScreen} />
    {driverSharedScreens(HomeStack)}
  </HomeStack.Navigator>
);

const DriverRoutesStackNav = () => (
  <RoutesStack.Navigator screenOptions={{ headerShown: false }}>
    <RoutesStack.Screen name="RoutesList" component={DriverRoutesScreen} />
    {driverSharedScreens(RoutesStack)}
  </RoutesStack.Navigator>
);

const DriverAccountStack = () => (
  <AccountStack.Navigator screenOptions={{ headerShown: false }}>
    <AccountStack.Screen name="DriverMoreMenu" component={DriverMoreHubScreen} />
    {driverSharedScreens(AccountStack)}
  </AccountStack.Navigator>
);

export const DriverTabNavigator: React.FC = () => {
  const { isDark, colors, palette } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <Tab.Navigator
      screenOptions={getDefaultTabScreenOptions(insets, { primary: colors.primary, palette, isDark })}
    >
      <Tab.Screen
        name="DriverHomeTab"
        component={DriverHomeStack}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="DriverRoutesTab"
        component={DriverRoutesStackNav}
        options={{
          tabBarLabel: 'Routes',
          tabBarIcon: ({ color, size }) => <Ionicons name="map-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="DriverAccountTab"
        component={DriverAccountStack}
        options={{
          tabBarLabel: 'Account',
          tabBarIcon: ({ color, size }) => <Ionicons name="person-outline" size={size} color={color} />,
        }}
      />
    </Tab.Navigator>
  );
};
