import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { AnnouncementsScreen } from '../../features/parent/screens/AnnouncementsScreen';
import { ChildAttendanceScreen } from '../../features/parent/screens/ChildAttendanceScreen';
import { ChildHomeworkScreen } from '../../features/parent/screens/ChildHomeworkScreen';
import { ChildHubScreen } from '../../features/parent/screens/ChildHubScreen';
import { ChildResultsScreen } from '../../features/parent/screens/ChildResultsScreen';
import { ParentInvoiceDetailScreen } from '../../features/parent/screens/ParentInvoiceDetailScreen';
import { ParentPaymentDetailScreen } from '../../features/parent/screens/ParentPaymentDetailScreen';
import { ParentReportCardDetailScreen } from '../../features/parent/screens/ParentReportCardDetailScreen';
import { ParentWalletHomeScreen } from '../../features/parent/screens/wallet/ParentWalletHomeScreen';
import { ParentWalletSavingPlanFormScreen } from '../../features/parent/screens/wallet/ParentWalletSavingPlanFormScreen';
import { ParentWalletSavingPlansScreen } from '../../features/parent/screens/wallet/ParentWalletSavingPlansScreen';
import { ParentWalletTopUpScreen } from '../../features/parent/screens/wallet/ParentWalletTopUpScreen';
import { ConcernsListScreen, RaiseConcernScreen, StudentDetailScreen } from '../../features/shared/screens';
import { DiaryChatScreen } from '../../features/shared/diary';
import { DiaryListScreen } from '../../features/parent/screens/DiaryListScreen';
import { LiveBusTrackScreen } from '../../features/parent/screens/LiveBusTrackScreen';
import { MpesaPromptScreen } from '../../features/parent/screens/MpesaPromptScreen';
import {
  ParentChildrenScreen,
  ParentFeesScreen,
  ParentHomeScreen,
} from '../../features/parent/screens/ParentScreens';
import { ParentAcademicScreen } from '../../features/parent/screens/ParentAcademicScreen';
import { ParentMoreScreen } from '../../features/parent/screens/ParentMoreScreen';
import { StudentStatementScreen } from '../../features/parent/screens/StudentStatementScreen';
import { TransportScreen } from '../../features/parent/screens/TransportScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { UsersAppHeaderChrome } from '../UsersAppHeaderChrome';
import { createUsersTabBar } from '../UsersPremiumTabBar';
import type { ParentStackParamList } from './parentStackTypes';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator<ParentStackParamList>();

/** Screens available from every tab stack (notifications deep links, settings, wallet). */
const parentSharedScreens = () => (
  <>
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
    <Stack.Screen name="ConcernsList" component={ConcernsListScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
    <Stack.Screen name="Settings" component={SettingsScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
    <Stack.Screen name="ReportCardDetail" component={ParentReportCardDetailScreen} />
    <Stack.Screen name="InvoiceDetail" component={ParentInvoiceDetailScreen} />
    <Stack.Screen name="PaymentDetail" component={ParentPaymentDetailScreen} />
    <Stack.Screen name="WalletHome" component={ParentWalletHomeScreen} />
    <Stack.Screen name="WalletTopUp" component={ParentWalletTopUpScreen} />
    <Stack.Screen name="WalletSavingPlans" component={ParentWalletSavingPlansScreen} />
    <Stack.Screen name="WalletSavingPlanForm" component={ParentWalletSavingPlanFormScreen} />
    <Stack.Screen name="MpesaPrompt" component={MpesaPromptScreen} />
  </>
);

const ParentHomeStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="ParentHome"
      component={ParentHomeScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Home"
            onMenuPress={() => navigation.getParent()?.navigate('ParentMoreTab' as never)}
            onSearchPress={() => navigation.getParent()?.navigate('ParentMoreTab' as never)}
            searchPrompt="Search menu…"
          />
        ),
      }}
    />
    <Stack.Screen name="ChildrenList" component={ParentChildrenScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    <Stack.Screen name="ChildResults" component={ChildResultsScreen} />
    <Stack.Screen name="ChildAttendance" component={ChildAttendanceScreen} />
    <Stack.Screen name="ChildHomework" component={ChildHomeworkScreen} />
    <Stack.Screen name="FeesHome" component={ParentFeesScreen} />
    <Stack.Screen name="AcademicHome" component={ParentAcademicScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    <Stack.Screen name="DiaryList" component={DiaryListScreen} />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
    <Stack.Screen name="Transport" component={TransportScreen} />
    <Stack.Screen name="LiveBusTrack" component={LiveBusTrackScreen} />
    {parentSharedScreens()}
  </Stack.Navigator>
);

const ParentChildrenStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="ChildrenList"
      component={ParentChildrenScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Children"
            onMenuPress={() => navigation.getParent()?.navigate('ParentMoreTab' as never)}
          />
        ),
      }}
    />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    <Stack.Screen name="ChildResults" component={ChildResultsScreen} />
    <Stack.Screen name="ChildAttendance" component={ChildAttendanceScreen} />
    <Stack.Screen name="ChildHomework" component={ChildHomeworkScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="Transport" component={TransportScreen} />
    <Stack.Screen name="LiveBusTrack" component={LiveBusTrackScreen} />
    {parentSharedScreens()}
  </Stack.Navigator>
);

const ParentFeesStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="FeesHome"
      component={ParentFeesScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Fees"
            onMenuPress={() => navigation.getParent()?.navigate('ParentMoreTab' as never)}
          />
        ),
      }}
    />
    <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    {parentSharedScreens()}
  </Stack.Navigator>
);

const ParentDiaryStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="DiaryList"
      component={DiaryListScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Diary"
            onMenuPress={() => navigation.getParent()?.navigate('ParentMoreTab' as never)}
          />
        ),
      }}
    />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    {parentSharedScreens()}
  </Stack.Navigator>
);

const ParentAcademicStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="AcademicHome"
      component={ParentAcademicScreen}
      options={{
        headerShown: true,
        header: ({ navigation }) => (
          <UsersAppHeaderChrome
            title="Academic"
            onMenuPress={() => navigation.getParent()?.navigate('ParentMoreTab' as never)}
          />
        ),
      }}
    />
    <Stack.Screen name="ChildResults" component={ChildResultsScreen} />
    <Stack.Screen name="ChildAttendance" component={ChildAttendanceScreen} />
    <Stack.Screen name="ChildHomework" component={ChildHomeworkScreen} />
    <Stack.Screen name="ChildHub" component={ChildHubScreen} />
    {parentSharedScreens()}
  </Stack.Navigator>
);

const ParentMoreStack = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen
      name="MoreMenu"
      component={ParentMoreScreen}
      options={{ headerShown: true, header: () => <UsersAppHeaderChrome title="More" /> }}
    />
    <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
    {parentSharedScreens()}
  </Stack.Navigator>
);

const parentTabBar = createUsersTabBar({
  ParentHomeTab: { label: 'Home', icon: 'home-outline', iconFocused: 'home', tone: 'blue' },
  ParentChildrenTab: { label: 'Children', icon: 'people-outline', iconFocused: 'people', tone: 'indigo' },
  ParentFeesTab: { label: 'Fees', icon: 'cash-outline', iconFocused: 'cash', tone: 'emerald' },
  ParentDiaryTab: { label: 'Diary', icon: 'chatbubbles-outline', iconFocused: 'chatbubbles', tone: 'cyan' },
  ParentAcademicTab: { label: 'Academic', icon: 'school-outline', iconFocused: 'school', tone: 'violet' },
  ParentMoreTab: { label: 'More', icon: 'menu-outline', iconFocused: 'menu', tone: 'amber' },
});

export const ParentTabNavigator: React.FC = () => {
  return (
    <Tab.Navigator screenOptions={{ headerShown: false }} tabBar={parentTabBar}>
      <Tab.Screen name="ParentHomeTab" component={ParentHomeStack} options={{ tabBarLabel: 'Home' }} />
      <Tab.Screen
        name="ParentChildrenTab"
        component={ParentChildrenStack}
        options={{ tabBarLabel: 'Children' }}
      />
      <Tab.Screen name="ParentFeesTab" component={ParentFeesStack} options={{ tabBarLabel: 'Fees' }} />
      <Tab.Screen name="ParentDiaryTab" component={ParentDiaryStack} options={{ tabBarLabel: 'Diary' }} />
      <Tab.Screen
        name="ParentAcademicTab"
        component={ParentAcademicStack}
        options={{ tabBarLabel: 'Academic' }}
      />
      <Tab.Screen name="ParentMoreTab" component={ParentMoreStack} options={{ tabBarLabel: 'More' }} />
    </Tab.Navigator>
  );
};
