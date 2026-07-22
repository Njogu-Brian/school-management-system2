import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '@erp/ui';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { LeaveApplyScreen } from '../../features/me/screens/LeaveApplyScreen';
import { MyAdvancesScreen } from '../../features/me/screens/MyAdvancesScreen';
import { MyLeaveListScreen } from '../../features/me/screens/MyLeaveListScreen';
import { MyPayslipsScreen } from '../../features/me/screens/MyPayslipsScreen';
import { MyProfileScreen } from '../../features/me/screens/MyProfileScreen';
import { StaffClockScreen } from '../../features/me/screens/StaffClockScreen';
import { NotificationsListScreen } from '../../features/notifications/screens/NotificationsListScreen';
import { SettingsScreen } from '../../features/settings/screens/SettingsScreen';
import { DiaryChatScreen, DiaryListScreen } from '../../features/shared/diary';
import { AnnouncementsListScreen } from '../../features/shared/screens/AnnouncementsListScreen';
import { StudentDetailScreen } from '../../features/shared/screens/StudentDetailScreen';
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
import { getDefaultTabScreenOptions } from '../tabBarConfig';
import type { TeacherStackParamList } from './teacherStackTypes';

const Stack = createStackNavigator<TeacherStackParamList>();
const Tab = createBottomTabNavigator();

function TeacherTabs() {
  const { isDark, colors, palette } = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <Tab.Navigator
      screenOptions={getDefaultTabScreenOptions(insets, { primary: colors.primary, palette, isDark })}
    >
      <Tab.Screen
        name="Home"
        component={TeacherHomeScreen}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({ color, size }) => <Ionicons name="home-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="Classes"
        component={TeacherClassesScreen}
        options={{
          tabBarLabel: 'Classes',
          tabBarIcon: ({ color, size }) => <Ionicons name="school-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="Attendance"
        component={MarkAttendanceScreen}
        options={{
          tabBarLabel: 'Attendance',
          tabBarIcon: ({ color, size }) => <Ionicons name="checkbox-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="Academics"
        component={TeacherAcademicsHubScreen}
        options={{
          tabBarLabel: 'Academics',
          tabBarIcon: ({ color, size }) => <Ionicons name="book-outline" size={size} color={color} />,
        }}
      />
      <Tab.Screen
        name="More"
        component={TeacherMoreHubScreen}
        options={{
          tabBarLabel: 'More',
          tabBarIcon: ({ color, size }) => <Ionicons name="menu-outline" size={size} color={color} />,
        }}
      />
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
  </Stack.Navigator>
);
