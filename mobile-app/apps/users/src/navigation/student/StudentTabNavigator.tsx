import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { AnnouncementsListScreen, ConcernsListScreen, RaiseConcernScreen } from '../../features/shared/screens';
import {
  StudentHomeworkScreen,
  StudentHomeScreen,
  StudentResultsScreen,
} from '../../features/student/screens/StudentScreens';
import { StudentMoreScreen } from '../../features/student/screens/StudentMoreScreen';
import { UsersAppHeaderChrome } from '../UsersAppHeaderChrome';
import { createUsersTabBar } from '../UsersPremiumTabBar';
import type { StudentStackParamList } from './studentStackTypes';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator<StudentStackParamList>();

const studentSharedScreens = () => (
  <>
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
    <Stack.Screen name="ConcernsList" component={ConcernsListScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
    <Stack.Screen name="Settings" component={SettingsScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
  </>
);

const StudentHomeStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="StudentHome"
      component={StudentHomeScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Home"
            onMenuPress={() => navigation.getParent()?.navigate('StudentMoreTab' as never)}
          />
        ),
      }}
    />
    <Stack.Screen name="Announcements" component={AnnouncementsListScreen} />
    {studentSharedScreens()}
  </Stack.Navigator>
);

const StudentHomeworkStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="StudentHomeworkMain"
      component={StudentHomeworkScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Homework"
            onMenuPress={() => navigation.getParent()?.navigate('StudentMoreTab' as never)}
          />
        ),
      }}
    />
    {studentSharedScreens()}
  </Stack.Navigator>
);

const StudentResultsStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="StudentResultsMain"
      component={StudentResultsScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Results"
            onMenuPress={() => navigation.getParent()?.navigate('StudentMoreTab' as never)}
          />
        ),
      }}
    />
    {studentSharedScreens()}
  </Stack.Navigator>
);

const StudentMoreStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="StudentMoreMenu"
      component={StudentMoreScreen}
      options={{ headerShown: true, header: () => <UsersAppHeaderChrome title="More" /> }}
    />
    {studentSharedScreens()}
  </Stack.Navigator>
);

const studentTabBar = createUsersTabBar({
  StudentHomeTab: { label: 'Home', icon: 'home-outline', iconFocused: 'home', tone: 'blue' },
  StudentHomeworkTab: {
    label: 'Homework',
    icon: 'document-text-outline',
    iconFocused: 'document-text',
    tone: 'indigo',
  },
  StudentResultsTab: { label: 'Results', icon: 'ribbon-outline', iconFocused: 'ribbon', tone: 'emerald' },
  StudentMoreTab: { label: 'More', icon: 'menu-outline', iconFocused: 'menu', tone: 'amber' },
});

export const StudentTabNavigator: React.FC = () => {
  return (
    <Tab.Navigator screenOptions={{ headerShown: false }} tabBar={studentTabBar}>
      <Tab.Screen name="StudentHomeTab" component={StudentHomeStack} options={{ tabBarLabel: 'Home' }} />
      <Tab.Screen
        name="StudentHomeworkTab"
        component={StudentHomeworkStack}
        options={{ tabBarLabel: 'Homework' }}
      />
      <Tab.Screen
        name="StudentResultsTab"
        component={StudentResultsStack}
        options={{ tabBarLabel: 'Results' }}
      />
      <Tab.Screen name="StudentMoreTab" component={StudentMoreStack} options={{ tabBarLabel: 'More' }} />
    </Tab.Navigator>
  );
};
