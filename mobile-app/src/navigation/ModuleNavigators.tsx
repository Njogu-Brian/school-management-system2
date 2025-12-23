import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { StudentsListScreen } from '@screens/Students/StudentsListScreen';
import { StudentDetailScreen } from '@screens/Students/StudentDetailScreen';
import { AddStudentScreen } from '@screens/Students/AddStudentScreen';
import { MarkAttendanceScreen } from '@screens/Attendance/MarkAttendanceScreen';
import { InvoicesListScreen } from '@screens/Finance/InvoicesListScreen';
import { RecordPaymentScreen } from '@screens/Finance/RecordPaymentScreen';
import { StaffDirectoryScreen } from '@screens/HR/StaffDirectoryScreen';
import { RoutesListScreen } from '@screens/Transport/RoutesListScreen';
import { LibraryBooksScreen } from '@screens/Library/LibraryBooksScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { MarksEntryScreen } from '@screens/Academics/MarksEntryScreen';
import { TimetableScreen } from '@screens/Academics/TimetableScreen';
import { ReportCardScreen } from '@screens/Academics/ReportCardScreen';
import { AssignmentsScreen } from '@screens/Academics/AssignmentsScreen';

const Stack = createStackNavigator();

// Placeholder screens
const PlaceholderScreen = ({ route }: any) => {
    return null;
};

export const StudentsNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="StudentsList" component={StudentsListScreen} />
            <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
            <Stack.Screen name="AddStudent" component={AddStudentScreen} />
            <Stack.Screen name="EditStudent" component={PlaceholderScreen} />
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
            <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
            <Stack.Screen name="Timetable" component={TimetableScreen} />
            <Stack.Screen name="ReportCard" component={ReportCardScreen} />
            <Stack.Screen name="Assignments" component={AssignmentsScreen} />
            <Stack.Screen name="ExamDetail" component={PlaceholderScreen} />
            <Stack.Screen name="CreateExam" component={PlaceholderScreen} />
            <Stack.Screen name="ViewMarks" component={PlaceholderScreen} />
            <Stack.Screen name="CreateAssignment" component={PlaceholderScreen} />
            <Stack.Screen name="AssignmentDetail" component={PlaceholderScreen} />
            <Stack.Screen name="ViewAssignment" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export const FinanceNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="InvoicesList" component={InvoicesListScreen} />
            <Stack.Screen name="InvoiceDetail" component={PlaceholderScreen} />
            <Stack.Screen name="CreateInvoice" component={PlaceholderScreen} />
            <Stack.Screen name="RecordPayment" component={RecordPaymentScreen} />
            <Stack.Screen name="StudentStatement" component={PlaceholderScreen} />
            <Stack.Screen name="FeeStructures" component={PlaceholderScreen} />
            <Stack.Screen name="Receipts" component={PlaceholderScreen} />
            <Stack.Screen name="Defaulters" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export const HRNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="StaffDirectory" component={StaffDirectoryScreen} />
            <Stack.Screen name="StaffDetail" component={PlaceholderScreen} />
            <Stack.Screen name="AddStaff" component={PlaceholderScreen} />
            <Stack.Screen name="LeaveManagement" component={PlaceholderScreen} />
            <Stack.Screen name="Payroll" component={PlaceholderScreen} />
        </Stack.Navigator>
    );
};

export const TransportNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="RoutesList" component={RoutesListScreen} />
            <Stack.Screen name="RouteDetail" component={PlaceholderScreen} />
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
