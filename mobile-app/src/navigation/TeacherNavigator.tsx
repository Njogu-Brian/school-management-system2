import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { TeacherDashboard } from '@screens/Dashboard/TeacherDashboard';
import { StudentsListScreen } from '@screens/Students/StudentsListScreen';
import { StudentDetailScreen } from '@screens/Students/StudentDetailScreen';
import { MarkAttendanceScreen } from '@screens/Attendance/MarkAttendanceScreen';
import { AttendanceRecordsScreen } from '@screens/Attendance/AttendanceRecordsScreen';
import { TimetableScreen } from '@screens/Academics/TimetableScreen';
import { AssignmentsScreen } from '@screens/Academics/AssignmentsScreen';
import { MarksEntryScreen } from '@screens/Academics/MarksEntryScreen';
import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { ExamMarksSetupScreen } from '@screens/Academics/ExamMarksSetupScreen';
import { ReportCardScreen } from '@screens/Academics/ReportCardScreen';
import { RoutesListScreen } from '@screens/Transport/RoutesListScreen';
import { RouteDetailScreen } from '@screens/Transport/RouteDetailScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { SettingsScreen } from '@screens/Settings/SettingsScreen';
import { LessonPlansScreen } from '@screens/Academics/LessonPlansScreen';
import { DiaryScreen } from '@screens/Academics/DiaryScreen';
import { MyProfileScreen } from '@screens/HR/MyProfileScreen';
import { MySalaryScreen } from '@screens/HR/MySalaryScreen';
import { LeaveManagementScreen } from '@screens/HR/LeaveManagementScreen';
import { ApplyLeaveScreen } from '@screens/HR/ApplyLeaveScreen';
import { SupervisedClassroomsScreen } from '@screens/SeniorTeacher/SupervisedClassroomsScreen';
import { SupervisedStaffScreen } from '@screens/SeniorTeacher/SupervisedStaffScreen';
import { FeeBalancesScreen } from '@screens/SeniorTeacher/FeeBalancesScreen';
import { RecordPaymentScreen } from '@screens/Finance/RecordPaymentScreen';
import { StudentStatementScreen } from '@screens/Finance/StudentStatementScreen';

const Stack = createStackNavigator();

// Lightweight placeholder for screens we might add later (e.g. assignment detail)
const PlaceholderScreen = () => null;

export const TeacherNavigator = () => {
    return (
        <Stack.Navigator
            initialRouteName="TeacherDashboard"
            screenOptions={{ headerShown: false }}
        >
            <Stack.Screen name="TeacherDashboard" component={TeacherDashboard} />
            {/* My Classes / Students */}
            <Stack.Screen name="MyClasses" component={StudentsListScreen} />
            <Stack.Screen name="StudentsList" component={StudentsListScreen} />
            <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
            <Stack.Screen name="RecordPayment" component={RecordPaymentScreen} />
            <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
            {/* Attendance */}
            <Stack.Screen name="MarkAttendance" component={MarkAttendanceScreen} />
            <Stack.Screen name="AttendanceRecords" component={AttendanceRecordsScreen} />
            {/* Academics */}
            <Stack.Screen name="Timetable" component={TimetableScreen} />
            <Stack.Screen name="Assignments" component={AssignmentsScreen} />
            <Stack.Screen name="LessonPlans" component={LessonPlansScreen} />
            <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
            <Stack.Screen name="ExamsList" component={ExamsListScreen} />
            <Stack.Screen name="ExamMarksSetup" component={ExamMarksSetupScreen} />
            <Stack.Screen name="ExamDetail" component={PlaceholderScreen} />
            <Stack.Screen name="ReportCard" component={ReportCardScreen} />
            {/* Transport, Diary, Profile, Salary */}
            <Stack.Screen name="Transport" component={RoutesListScreen} />
            <Stack.Screen name="RouteDetail" component={RouteDetailScreen} />
            <Stack.Screen name="Diary" component={DiaryScreen} />
            <Stack.Screen name="MyProfile" component={MyProfileScreen} />
            <Stack.Screen name="MySalary" component={MySalaryScreen} />
            <Stack.Screen name="Leave" component={LeaveManagementScreen} />
            <Stack.Screen name="ApplyLeave" component={ApplyLeaveScreen} />
            {/* Senior Teacher only */}
            <Stack.Screen name="SupervisedClassrooms" component={SupervisedClassroomsScreen} />
            <Stack.Screen name="SupervisedStaff" component={SupervisedStaffScreen} />
            <Stack.Screen name="FeeBalances" component={FeeBalancesScreen} />
            {/* Settings & Notifications */}
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Settings" component={SettingsScreen} />
            {/* Placeholders for nested flows */}
            <Stack.Screen name="AssignmentDetail" component={PlaceholderScreen} />
            <Stack.Screen name="CreateAssignment" component={PlaceholderScreen} />
            <Stack.Screen name="LessonPlanDetail" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};
