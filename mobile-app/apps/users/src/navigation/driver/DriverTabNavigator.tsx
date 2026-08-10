import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
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
import { PayslipDetailScreen } from '../../features/me/screens/PayslipDetailScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { StaffClockScreen } from '../../features/me/screens/StaffClockScreen';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { ConcernsListScreen, RaiseConcernScreen } from '../../features/shared/screens';
import { UsersAppHeaderChrome } from '../UsersAppHeaderChrome';
import { createUsersTabBar } from '../UsersPremiumTabBar';
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
    <Stack.Screen name="PayslipDetail" component={PayslipDetailScreen} />
    <Stack.Screen name="MyAdvances" component={MyAdvancesScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
    <Stack.Screen name="DriverSettings" component={SettingsScreen} />
    <Stack.Screen name="ConcernsList" component={ConcernsListScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
  </>
);

const DriverHomeStack = () => (
  <HomeStack.Navigator screenOptions={{ headerShown: false }}>
    <HomeStack.Screen
      name="DriverHomeMain"
      component={DriverHomeScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Home"
            onMenuPress={() => navigation.getParent()?.navigate('DriverAccountTab' as never)}
          />
        ),
      }}
    />
    {driverSharedScreens(HomeStack)}
  </HomeStack.Navigator>
);

const DriverRoutesStackNav = () => (
  <RoutesStack.Navigator screenOptions={{ headerShown: false }}>
    <RoutesStack.Screen
      name="RoutesList"
      component={DriverRoutesScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Routes"
            onMenuPress={() => navigation.getParent()?.navigate('DriverAccountTab' as never)}
          />
        ),
      }}
    />
    {driverSharedScreens(RoutesStack)}
  </RoutesStack.Navigator>
);

const DriverAccountStack = () => (
  <AccountStack.Navigator screenOptions={{ headerShown: false }}>
    <AccountStack.Screen
      name="DriverMoreMenu"
      component={DriverMoreHubScreen}
      options={{ headerShown: true, header: () => <UsersAppHeaderChrome title="Account" /> }}
    />
    {driverSharedScreens(AccountStack)}
  </AccountStack.Navigator>
);

const driverTabBar = createUsersTabBar({
  DriverHomeTab: { label: 'Home', icon: 'home-outline', iconFocused: 'home', tone: 'blue' },
  DriverRoutesTab: { label: 'Routes', icon: 'map-outline', iconFocused: 'map', tone: 'indigo' },
  DriverAccountTab: { label: 'Account', icon: 'person-outline', iconFocused: 'person', tone: 'cyan' },
});

export const DriverTabNavigator: React.FC = () => {
  return (
    <Tab.Navigator screenOptions={{ headerShown: false }} tabBar={driverTabBar}>
      <Tab.Screen name="DriverHomeTab" component={DriverHomeStack} options={{ tabBarLabel: 'Home' }} />
      <Tab.Screen
        name="DriverRoutesTab"
        component={DriverRoutesStackNav}
        options={{ tabBarLabel: 'Routes' }}
      />
      <Tab.Screen
        name="DriverAccountTab"
        component={DriverAccountStack}
        options={{ tabBarLabel: 'Account' }}
      />
    </Tab.Navigator>
  );
};
