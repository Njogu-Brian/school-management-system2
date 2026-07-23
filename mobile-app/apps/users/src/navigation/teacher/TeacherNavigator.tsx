import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { LeaveApplyScreen } from '../../features/me/screens/LeaveApplyScreen';
import { MyAdvancesScreen } from '../../features/me/screens/MyAdvancesScreen';
import { MyLeaveListScreen } from '../../features/me/screens/MyLeaveListScreen';
import { MyPayslipsScreen } from '../../features/me/screens/MyPayslipsScreen';
import { PayslipDetailScreen } from '../../features/me/screens/PayslipDetailScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { StaffClockScreen } from '../../features/me/screens/StaffClockScreen';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { DiaryChatScreen, DiaryListScreen } from '../../features/shared/diary';
import {
  AnnouncementsListScreen,
  ConcernsListScreen,
  RaiseConcernScreen,
  StudentDetailScreen,
} from '../../features/shared/screens';
import {
  AssignmentDetailScreen,
  AssignmentsHubScreen,
} from '../../features/teacher/screens/AssignmentsHubScreen';
import { CreateAssignmentScreen } from '../../features/teacher/screens/CreateAssignmentScreen';
import { LessonPlanDetailScreen } from '../../features/teacher/screens/LessonPlanDetailScreen';
import { CreateLessonPlanScreen } from '../../features/teacher/screens/CreateLessonPlanScreen';
import { LessonPlanReviewDetailScreen } from '../../features/teacher/screens/LessonPlanReviewDetailScreen';
import { LessonPlanReviewQueueScreen } from '../../features/teacher/screens/LessonPlanReviewQueueScreen';
import { LessonPlansHubScreen } from '../../features/teacher/screens/LessonPlansHubScreen';
import { MarkAttendanceScreen } from '../../features/teacher/screens/MarkAttendanceScreen';
import { MarksEntryScreen } from '../../features/teacher/screens/MarksEntryScreen';
import { MarksExamSetupScreen } from '../../features/teacher/screens/MarksExamSetupScreen';
import { MarksHubScreen } from '../../features/teacher/screens/MarksHubScreen';
import { MarksMatrixEntryScreen } from '../../features/teacher/screens/MarksMatrixEntryScreen';
import { MarksMatrixSetupScreen } from '../../features/teacher/screens/MarksMatrixSetupScreen';
import { RequirementDetailScreen } from '../../features/teacher/screens/RequirementDetailScreen';
import { RequirementsHubScreen } from '../../features/teacher/screens/RequirementsHubScreen';
import { TeacherAcademicsHubScreen } from '../../features/teacher/screens/TeacherAcademicsHubScreen';
import { TeacherClassesScreen } from '../../features/teacher/screens/TeacherClassesScreen';
import { TeacherHomeScreen } from '../../features/teacher/screens/TeacherHomeScreen';
import { TeacherMoreHubScreen } from '../../features/teacher/screens/TeacherMoreHubScreen';
import { TeacherTransportScreen } from '../../features/teacher/screens/TeacherTransportScreen';
import { TimetableHubScreen } from '../../features/teacher/screens/TimetableHubScreen';
import { UsersAppHeaderChrome } from '../UsersAppHeaderChrome';
import { createUsersTabBar } from '../UsersPremiumTabBar';
import type { TeacherStackParamList } from './teacherStackTypes';

const Stack = createStackNavigator<TeacherStackParamList>();
const Tab = createBottomTabNavigator();

const TAB_TITLES: Record<string, string> = {
  Home: 'Home',
  Classes: 'My classes',
  Attendance: 'Attendance',
  Academics: 'Academics',
  More: 'More',
};

const teacherTabBar = createUsersTabBar({
  Home: { label: 'Home', icon: 'home-outline', iconFocused: 'home', tone: 'blue' },
  Classes: { label: 'Classes', icon: 'school-outline', iconFocused: 'school', tone: 'indigo' },
  Attendance: { label: 'Attendance', icon: 'checkbox-outline', iconFocused: 'checkbox', tone: 'emerald' },
  Academics: { label: 'Academics', icon: 'book-outline', iconFocused: 'book', tone: 'cyan' },
  More: { label: 'More', icon: 'menu-outline', iconFocused: 'menu', tone: 'amber' },
});

function TeacherTabs() {
  return (
    <Tab.Navigator
      tabBar={teacherTabBar}
      screenOptions={({ route, navigation }) => ({
        headerShown: true,
        header: () => (
          <UsersAppHeaderChrome
            title={TAB_TITLES[route.name] ?? route.name}
            onMenuPress={() => navigation.navigate('More' as never)}
          />
        ),
      })}
    >
      <Tab.Screen name="Home" component={TeacherHomeScreen} />
      <Tab.Screen name="Classes" component={TeacherClassesScreen} />
      <Tab.Screen name="Attendance" component={MarkAttendanceScreen} />
      <Tab.Screen name="Academics" component={TeacherAcademicsHubScreen} />
      <Tab.Screen name="More" component={TeacherMoreHubScreen} />
    </Tab.Navigator>
  );
}

export const TeacherNavigator: React.FC = () => (
  <Stack.Navigator initialRouteName="Main" screenOptions={{ headerShown: false }}>
    <Stack.Screen name="Main" component={TeacherTabs} />
    <Stack.Screen name="MarkAttendance" component={MarkAttendanceScreen} />
    <Stack.Screen name="MarksHub" component={MarksHubScreen} />
    <Stack.Screen name="MarksMatrixSetup" component={MarksMatrixSetupScreen} />
    <Stack.Screen name="MarksMatrixEntry" component={MarksMatrixEntryScreen} />
    <Stack.Screen name="MarksExamSetup" component={MarksExamSetupScreen} />
    <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
    <Stack.Screen name="LessonPlansHub" component={LessonPlansHubScreen} />
    <Stack.Screen name="LessonPlanDetail" component={LessonPlanDetailScreen} />
    <Stack.Screen name="CreateLessonPlan" component={CreateLessonPlanScreen} />
    <Stack.Screen name="TimetableHub" component={TimetableHubScreen} />
    <Stack.Screen name="RequirementsHub" component={RequirementsHubScreen} />
    <Stack.Screen name="RequirementDetail" component={RequirementDetailScreen} />
    <Stack.Screen name="StaffClock" component={StaffClockScreen} />
    <Stack.Screen name="LeaveApply" component={LeaveApplyScreen} />
    <Stack.Screen name="MyLeaveList" component={MyLeaveListScreen} />
    <Stack.Screen name="MyPayslips" component={MyPayslipsScreen} />
    <Stack.Screen name="PayslipDetail" component={PayslipDetailScreen} />
    <Stack.Screen name="MyProfile" component={MyProfileScreen} />
    <Stack.Screen name="TeacherTransportHub" component={TeacherTransportScreen} />
    <Stack.Screen name="LessonPlanReview" component={LessonPlanReviewQueueScreen} />
    <Stack.Screen name="LessonPlanReviewDetail" component={LessonPlanReviewDetailScreen} />
    <Stack.Screen name="DiaryList" component={DiaryListScreen} />
    <Stack.Screen name="DiaryChat" component={DiaryChatScreen} />
    <Stack.Screen name="Announcements" component={AnnouncementsListScreen} />
    <Stack.Screen name="Notifications" component={NotificationsListScreen} />
    <Stack.Screen name="Settings" component={SettingsScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    <Stack.Screen name="AssignmentsHub" component={AssignmentsHubScreen} />
    <Stack.Screen name="CreateAssignment" component={CreateAssignmentScreen} />
    <Stack.Screen name="AssignmentDetail" component={AssignmentDetailScreen} />
    <Stack.Screen name="MyAdvances" component={MyAdvancesScreen} />
    <Stack.Screen name="ConcernsList" component={ConcernsListScreen} />
    <Stack.Screen name="RaiseConcern" component={RaiseConcernScreen} />
  </Stack.Navigator>
);
