import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { StudentsListScreen } from '@screens/Students/StudentsListScreen';
import { StudentDetailScreen } from '@screens/Students/StudentDetailScreen';
import { AddStudentScreen } from '@screens/Students/AddStudentScreen';
import { EditStudentScreen } from '@screens/Students/StudentFormScreen';
import { MarkAttendanceScreen } from '@screens/Attendance/MarkAttendanceScreen';
import { FinanceHomeScreen } from '@screens/Finance/FinanceHomeScreen';
import { InvoicesListScreen } from '@screens/Finance/InvoicesListScreen';
import { InvoiceDetailScreen } from '@screens/Finance/InvoiceDetailScreen';
import { PaymentsListScreen } from '@screens/Finance/PaymentsListScreen';
import { PaymentDetailScreen } from '@screens/Finance/PaymentDetailScreen';
import { RecordPaymentScreen } from '@screens/Finance/RecordPaymentScreen';
import { StudentStatementScreen } from '@screens/Finance/StudentStatementScreen';
import { MpesaWaitingWebViewScreen } from '@screens/Finance/MpesaWaitingWebViewScreen';
import { StaffDirectoryScreen } from '@screens/HR/StaffDirectoryScreen';
import { StaffDetailScreen } from '@screens/HR/StaffDetailScreen';
import { StaffEditScreen } from '@screens/HR/StaffEditScreen';
import { PayrollRecordsScreen } from '@screens/HR/PayrollRecordsScreen';
import { RoutesListScreen } from '@screens/Transport/RoutesListScreen';
import { RouteDetailScreen } from '@screens/Transport/RouteDetailScreen';
import { LibraryBooksScreen } from '@screens/Library/LibraryBooksScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { ExamMarksSetupScreen } from '@screens/Academics/ExamMarksSetupScreen';
import { MarksEntryScreen } from '@screens/Academics/MarksEntryScreen';
import { LeaveManagementScreen } from '@screens/HR/LeaveManagementScreen';
import { ApplyLeaveScreen } from '@screens/HR/ApplyLeaveScreen';
import { TimetableScreen } from '@screens/Academics/TimetableScreen';
import { ReportCardScreen } from '@screens/Academics/ReportCardScreen';
import { AssignmentsScreen } from '@screens/Academics/AssignmentsScreen';
import { CreateAssignmentScreen } from '@screens/Academics/CreateAssignmentScreen';
import { AssignmentDetailScreen } from '@screens/Academics/AssignmentDetailScreen';

const Stack = createStackNavigator();

// Placeholder screens
const PlaceholderScreen = ({ route: _route }: any) => {
    return null;
};

export const StudentsNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="StudentsList" component={StudentsListScreen} />
            <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
            <Stack.Screen name="AddStudent" component={AddStudentScreen} />
            <Stack.Screen name="EditStudent" component={EditStudentScreen} />
            <Stack.Screen name="RecordPayment" component={RecordPaymentScreen} />
            <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
        </Stack.Navigator>
    );
};

export const AttendanceNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="MarkAttendance" component={MarkAttendanceScreen} />
            <Stack.Screen name="AttendanceRecords" component={PlaceholderScreen} />
            <Stack.Screen name="AttendanceAnalytics" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export const AcademicsNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="ExamsList" component={ExamsListScreen} />
            <Stack.Screen name="ExamMarksSetup" component={ExamMarksSetupScreen} />
            <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
            <Stack.Screen name="Timetable" component={TimetableScreen} />
            <Stack.Screen name="ReportCard" component={ReportCardScreen} />
            <Stack.Screen name="Assignments" component={AssignmentsScreen} />
            <Stack.Screen name="ExamDetail" component={PlaceholderScreen} />
            <Stack.Screen name="CreateExam" component={PlaceholderScreen} />
            <Stack.Screen name="ViewMarks" component={PlaceholderScreen} />
            <Stack.Screen name="CreateAssignment" component={CreateAssignmentScreen} />
            <Stack.Screen name="AssignmentDetail" component={AssignmentDetailScreen} />
            <Stack.Screen name="ViewAssignment" component={AssignmentDetailScreen} />
        </Stack.Navigator>
    );
};

export const FinanceNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="FinanceHome">
            <Stack.Screen name="FinanceHome" component={FinanceHomeScreen} />
            <Stack.Screen name="InvoicesList" component={InvoicesListScreen} />
            <Stack.Screen name="InvoiceDetail" component={InvoiceDetailScreen} />
            <Stack.Screen name="PaymentsList" component={PaymentsListScreen} />
            <Stack.Screen name="PaymentDetail" component={PaymentDetailScreen} />
            <Stack.Screen name="RecordPayment" component={RecordPaymentScreen} />
            <Stack.Screen name="StudentStatement" component={StudentStatementScreen} />
            <Stack.Screen name="FeeStructures" component={PlaceholderScreen} />
            <Stack.Screen name="Receipts" component={PlaceholderScreen} />
            <Stack.Screen name="Defaulters" component={PlaceholderScreen} />
            <Stack.Screen name="MpesaWaitingWeb" component={MpesaWaitingWebViewScreen} />
        </Stack.Navigator>
    );
};

export const HRNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="StaffDirectory" component={StaffDirectoryScreen} />
            <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
            <Stack.Screen name="StaffEdit" component={StaffEditScreen} />
            <Stack.Screen name="PayrollRecords" component={PayrollRecordsScreen} />
            <Stack.Screen name="AddStaff" component={PlaceholderScreen} />
            <Stack.Screen name="LeaveManagement" component={LeaveManagementScreen} />
            <Stack.Screen name="ApplyLeave" component={ApplyLeaveScreen} />
            <Stack.Screen name="Payroll" component={PayrollRecordsScreen} />
        </Stack.Navigator>
    );
};

export const TransportNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="RoutesList" component={RoutesListScreen} />
            <Stack.Screen name="RouteDetail" component={RouteDetailScreen} />
            <Stack.Screen name="AddRoute" component={PlaceholderScreen} />
            <Stack.Screen name="Vehicles" component={PlaceholderScreen} />
            <Stack.Screen name="Trips" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export const LibraryNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="LibraryBooks" component={LibraryBooksScreen} />
            <Stack.Screen name="BookDetail" component={PlaceholderScreen} />
            <Stack.Screen name="AddBook" component={PlaceholderScreen} />
            <Stack.Screen name="Borrowings" component={PlaceholderScreen} />
            <Stack.Screen name="LibraryCards" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export const CommunicationNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
            <Stack.Screen name="AnnouncementDetail" component={PlaceholderScreen} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Messages" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export { PaymentsNavigator } from './PaymentsNavigator';
