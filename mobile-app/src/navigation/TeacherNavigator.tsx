import React from 'react';
import { View, Text } from 'react-native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { TeacherDashboard } from '@screens/Dashboard/TeacherDashboard';
import { TeacherMoreHubScreen } from '@screens/Dashboard/TeacherMoreHubScreen';
import { StudentsListScreen } from '@screens/Students/StudentsListScreen';
import { StudentDetailScreen } from '@screens/Students/StudentDetailScreen';
import { MarkAttendanceScreen } from '@screens/Attendance/MarkAttendanceScreen';
import { TeacherClockScreen } from '@screens/Attendance/TeacherClockScreen';
import { AttendanceRecordsScreen } from '@screens/Attendance/AttendanceRecordsScreen';
import { TimetableScreen } from '@screens/Academics/TimetableScreen';
import { AssignmentsScreen } from '@screens/Academics/AssignmentsScreen';
import { MarksEntryScreen } from '@screens/Academics/MarksEntryScreen';
import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { ExamMarksSetupScreen } from '@screens/Academics/ExamMarksSetupScreen';
import { MarksMatrixSetupScreen } from '@screens/Academics/MarksMatrixSetupScreen';
import { MarksMatrixEntryScreen } from '@screens/Academics/MarksMatrixEntryScreen';
import { ReportCardScreen } from '@screens/Academics/ReportCardScreen';
import { RouteDetailScreen } from '@screens/Transport/RouteDetailScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { SettingsScreen } from '@screens/Settings/SettingsScreen';
import { LessonPlansScreen } from '@screens/Academics/LessonPlansScreen';
import { DiaryScreen } from '@screens/Academics/DiaryScreen';
import { CreateAssignmentScreen } from '@screens/Academics/CreateAssignmentScreen';
import { AssignmentDetailScreen } from '@screens/Academics/AssignmentDetailScreen';
import { MyProfileScreen } from '@screens/HR/MyProfileScreen';
import { MySalaryScreen } from '@screens/HR/MySalaryScreen';
import { LeaveManagementScreen } from '@screens/HR/LeaveManagementScreen';
import { ApplyLeaveScreen } from '@screens/HR/ApplyLeaveScreen';
import { StaffEditScreen } from '@screens/HR/StaffEditScreen';
import { SupervisedClassroomsScreen } from '@screens/SeniorTeacher/SupervisedClassroomsScreen';
import { SupervisedStaffScreen } from '@screens/SeniorTeacher/SupervisedStaffScreen';
import { FeeBalancesScreen } from '@screens/SeniorTeacher/FeeBalancesScreen';
import { RecordPaymentScreen } from '@screens/Finance/RecordPaymentScreen';
import { StudentStatementScreen } from '@screens/Finance/StudentStatementScreen';
import { TeacherRequirementsScreen } from '@screens/Requirements/TeacherRequirementsScreen';
import { TeacherRequirementDetailScreen } from '@screens/Requirements/TeacherRequirementDetailScreen';
import { TeacherTransportListScreen } from '@screens/Transport/TeacherTransportListScreen';
import { useTheme } from '@contexts/ThemeContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

const ComingSoonScreen = () => (
    <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 }}>
        <Text style={{ textAlign: 'center' }}>This screen is not available in the app yet.</Text>
    </View>
);

function TeacherTabs() {
    const { isDark, colors } = useTheme();
    const insets = useSafeAreaInsets();
    return (
        <Tab.Navigator
            screenOptions={{
                headerShown: false,
                tabBarActiveTintColor: colors.primary,
                tabBarInactiveTintColor: isDark ? colors.textSubDark : colors.textSubLight,
                tabBarStyle: {
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderTopColor: isDark ? colors.borderDark : colors.borderLight,
                    height: 56 + insets.bottom,
                    paddingTop: 6,
                    paddingBottom: Math.max(insets.bottom, 6),
                },
                tabBarItemStyle: { paddingVertical: 0 },
            }}
        >
            <Tab.Screen
                name="Home"
                component={TeacherDashboard}
                options={{
                    tabBarLabel: 'Home',
                    tabBarIcon: ({ color, size }) => <Icon name="home" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="Classes"
                component={StudentsListScreen}
                initialParams={{ title: 'My classes', hint: 'Students in your assigned classes' }}
                options={{
                    tabBarLabel: 'Classes',
                    tabBarIcon: ({ color, size }) => <Icon name="school" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="Attendance"
                component={MarkAttendanceScreen}
                options={{
                    tabBarLabel: 'Attendance',
                    tabBarIcon: ({ color, size }) => <Icon name="fact-check" size={size} color={color} />,
                }}
            />
            <Tab.Screen
                name="More"
                component={TeacherMoreHubScreen}
                options={{
                    tabBarLabel: 'More',
                    tabBarIcon: ({ color, size }) => <Icon name="menu" size={size} color={color} />,
                }}
            />
        </Tab.Navigator>
    );
}

/** Teacher / senior teacher: bottom tabs + stack for detail flows (aligned with portal class scope). */
export const TeacherNavigator = () => {
    return (
        <Stack.Navigator initialRouteName="Main" screenOptions={{ headerShown: false }}>
            <Stack.Screen name="Main" component={TeacherTabs} />
            <Stack.Screen name="MyClasses" component={StudentsListScreen} />
            <Stack.Screen name="StudentsList" component={StudentsListScreen} />
            <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
            <Stack.Screen name="RecordPayment" component={RecordPaymentScreen} />
            <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
            <Stack.Screen name="MarkAttendance" component={MarkAttendanceScreen} />
            <Stack.Screen name="TeacherClock" component={TeacherClockScreen} />
            <Stack.Screen name="AttendanceRecords" component={AttendanceRecordsScreen} />
            <Stack.Screen name="Timetable" component={TimetableScreen} />
            <Stack.Screen name="Assignments" component={AssignmentsScreen} />
            <Stack.Screen name="LessonPlans" component={LessonPlansScreen} />
            <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
            <Stack.Screen name="MarksMatrixSetup" component={MarksMatrixSetupScreen} />
            <Stack.Screen name="MarksMatrixEntry" component={MarksMatrixEntryScreen} />
            <Stack.Screen name="ExamsList" component={ExamsListScreen} />
            <Stack.Screen name="ExamMarksSetup" component={ExamMarksSetupScreen} />
            <Stack.Screen name="ExamDetail" component={ComingSoonScreen} />
            <Stack.Screen name="ReportCard" component={ReportCardScreen} />
            <Stack.Screen name="Transport" component={TeacherTransportListScreen} />
            <Stack.Screen name="TeacherTransport" component={TeacherTransportListScreen} />
            <Stack.Screen name="RouteDetail" component={RouteDetailScreen} />
            <Stack.Screen name="Diary" component={DiaryScreen} />
            <Stack.Screen name="MyProfile" component={MyProfileScreen} />
            <Stack.Screen name="StaffEdit" component={StaffEditScreen} />
            <Stack.Screen name="MySalary" component={MySalaryScreen} />
            <Stack.Screen name="Leave" component={LeaveManagementScreen} />
            <Stack.Screen name="ApplyLeave" component={ApplyLeaveScreen} />
            <Stack.Screen name="SupervisedClassrooms" component={SupervisedClassroomsScreen} />
            <Stack.Screen name="SupervisedStaff" component={SupervisedStaffScreen} />
            <Stack.Screen name="FeeBalances" component={FeeBalancesScreen} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Settings" component={SettingsScreen} />
            <Stack.Screen name="AssignmentDetail" component={AssignmentDetailScreen} />
            <Stack.Screen name="CreateAssignment" component={CreateAssignmentScreen} />
            <Stack.Screen name="LessonPlanDetail" component={ComingSoonScreen} />
            <Stack.Screen name="TeacherRequirements" component={TeacherRequirementsScreen} />
            <Stack.Screen name="TeacherRequirementDetail" component={TeacherRequirementDetailScreen} />
        </Stack.Navigator>
    );
};
