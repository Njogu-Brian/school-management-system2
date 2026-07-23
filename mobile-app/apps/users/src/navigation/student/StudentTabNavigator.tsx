import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '@erp/ui';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { AnnouncementsListScreen } from '../../features/shared/screens/AnnouncementsListScreen';
import {
  StudentHomeworkScreen,
  StudentHomeScreen,
  StudentResultsScreen,
} from '../../features/student/screens/StudentScreens';
import { StudentMoreScreen } from '../../features/student/screens/StudentMoreScreen';
import { getDefaultTabScreenOptions } from '../tabBarConfig';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const StudentHomeStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StudentHome" component={StudentHomeScreen} />
    <Stack.Screen name="Announcements" component={AnnouncementsListScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
  </Stack.Navigator>
);

const StudentHomeworkStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StudentHomeworkMain" component={StudentHomeworkScreen} />
  </Stack.Navigator>
);

const StudentResultsStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StudentResultsMain" component={StudentResultsScreen} />
  </Stack.Navigator>
);

const StudentMoreStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StudentMoreMenu" component={StudentMoreScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
    <Stack.Screen name="Settings" component={SettingsScreen} />
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
  </Stack.Navigator>
);

export const StudentTabNavigator: React.FC = () => {
  const { isDark, colors, palette } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <Tab.Navigator
      screenOptions={getDefaultTabScreenOptions(insets, { primary: colors.primary, palette, isDark })}
    >
      <Tab.Screen
        name="StudentHomeTab"
        component={StudentHomeStack}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="StudentHomeworkTab"
        component={StudentHomeworkStack}
        options={{
          tabBarLabel: 'Homework',
          tabBarIcon: ({ color, size }) => <Ionicons name="document-text-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="StudentResultsTab"
        component={StudentResultsStack}
        options={{
          tabBarLabel: 'Results',
          tabBarIcon: ({ color, size }) => <Ionicons name="ribbon-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="StudentMoreTab"
        component={StudentMoreStack}
        options={{
          tabBarLabel: 'More',
          tabBarIcon: ({ color, size }) => <Ionicons name="menu-outline" size={size} color={color} />,
        }}
      />
    </Tab.Navigator>
  );
};
