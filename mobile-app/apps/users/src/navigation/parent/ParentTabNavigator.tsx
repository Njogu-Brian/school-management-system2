import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '@erp/ui';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { AnnouncementsScreen } from '../../features/parent/screens/AnnouncementsScreen';
import { ChildAttendanceScreen } from '../../features/parent/screens/ChildAttendanceScreen';
import { ChildHomeworkScreen } from '../../features/parent/screens/ChildHomeworkScreen';
import { ChildHubScreen } from '../../features/parent/screens/ChildHubScreen';
import { ChildResultsScreen } from '../../features/parent/screens/ChildResultsScreen';
import { ConcernsListScreen } from '../../features/parent/screens/ConcernsListScreen';
import { DiaryChatScreen } from '../../features/shared/diary';
import { DiaryListScreen } from '../../features/parent/screens/DiaryListScreen';
import { LiveBusTrackScreen } from '../../features/parent/screens/LiveBusTrackScreen';
import { MpesaPromptScreen } from '../../features/parent/screens/MpesaPromptScreen';
import {
  ParentChildrenScreen,
  ParentFeesScreen,
  ParentHomeScreen,
} from '../../features/parent/screens/ParentScreens';
import { ParentMoreScreen } from '../../features/parent/screens/ParentMoreScreen';
import { RaiseConcernScreen } from '../../features/parent/screens/RaiseConcernScreen';
import { StudentStatementScreen } from '../../features/parent/screens/StudentStatementScreen';
import { TransportScreen } from '../../features/parent/screens/TransportScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { StudentDetailScreen } from '../../features/shared/screens/StudentDetailScreen';
import { getDefaultTabScreenOptions } from '../tabBarConfig';
import type { ParentStackParamList } from './parentStackTypes';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator<ParentStackParamList>();

const ParentHomeStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="ParentHome" component={ParentHomeScreen} />
    <Stack.Screen name="ChildrenList" component={ParentChildrenScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    <Stack.Screen name="ChildResults" component={ChildResultsScreen} />
    <Stack.Screen name="ChildAttendance" component={ChildAttendanceScreen} />
    <Stack.Screen name="ChildHomework" component={ChildHomeworkScreen} />
    <Stack.Screen name="FeesHome" component={ParentFeesScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    <Stack.Screen name="MpesaPrompt" component={MpesaPromptScreen} />
    <Stack.Screen name="DiaryList" component={DiaryListScreen} />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
    <Stack.Screen name="ConcernsList" component={ConcernsListScreen} />
    <Stack.Screen name="Transport" component={TransportScreen} />
    <Stack.Screen name="LiveBusTrack" component={LiveBusTrackScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
    <Stack.Screen name="Settings" component={SettingsScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
  </Stack.Navigator>
);

const ParentChildrenStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="ChildrenList" component={ParentChildrenScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    <Stack.Screen name="ChildResults" component={ChildResultsScreen} />
    <Stack.Screen name="ChildAttendance" component={ChildAttendanceScreen} />
    <Stack.Screen name="ChildHomework" component={ChildHomeworkScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    <Stack.Screen name="MpesaPrompt" component={MpesaPromptScreen} />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="Transport" component={TransportScreen} />
    <Stack.Screen name="LiveBusTrack" component={LiveBusTrackScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
  </Stack.Navigator>
);

const ParentFeesStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="FeesHome" component={ParentFeesScreen} />
    <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    <Stack.Screen name="MpesaPrompt" component={MpesaPromptScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
  </Stack.Navigator>
);

const ParentDiaryStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="DiaryList" component={DiaryListScreen} />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
  </Stack.Navigator>
);

const ParentMoreStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="MoreMenu" component={ParentMoreScreen} />
    <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
    <Stack.Screen name="ConcernsList" component={ConcernsListScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
    <Stack.Screen name="Settings" component={SettingsScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
  </Stack.Navigator>
);

export const ParentTabNavigator: React.FC = () => {
  const { isDark, colors, palette } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <Tab.Navigator
      screenOptions={getDefaultTabScreenOptions(insets, { primary: colors.primary, palette, isDark })}
    >
      <Tab.Screen
        name="ParentHomeTab"
        component={ParentHomeStack}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="ParentChildrenTab"
        component={ParentChildrenStack}
        options={{
          tabBarLabel: 'Children',
          tabBarIcon: ({ color, size }) => <Ionicons name="people-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="ParentFeesTab"
        component={ParentFeesStack}
        options={{
          tabBarLabel: 'Fees',
          tabBarIcon: ({ color, size }) => <Ionicons name="wallet-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="ParentDiaryTab"
        component={ParentDiaryStack}
        options={{
          tabBarLabel: 'Diary',
          tabBarIcon: ({ color, size }) => <Ionicons name="chatbubbles-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="ParentMoreTab"
        component={ParentMoreStack}
        options={{
          tabBarLabel: 'More',
          tabBarIcon: ({ color, size }) => <Ionicons name="menu-outline" size={size} color={color} />,
        }}
      />
    </Tab.Navigator>
  );
};
