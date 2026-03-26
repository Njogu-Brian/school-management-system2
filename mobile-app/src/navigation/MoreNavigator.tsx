import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { MoreScreen } from '@screens/More/MoreScreen';
import { StaffDirectoryScreen } from '@screens/HR/StaffDirectoryScreen';
import { RoutesListScreen } from '@screens/Transport/RoutesListScreen';
import { RouteDetailScreen } from '@screens/Transport/RouteDetailScreen';
import { LibraryBooksScreen } from '@screens/Library/LibraryBooksScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { StaffDetailScreen } from '@screens/HR/StaffDetailScreen';
import { StaffEditScreen } from '@screens/HR/StaffEditScreen';
import { PayrollRecordsScreen } from '@screens/HR/PayrollRecordsScreen';
import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { ExamMarksSetupScreen } from '@screens/Academics/ExamMarksSetupScreen';
import { MarksEntryScreen } from '@screens/Academics/MarksEntryScreen';
import { LeaveManagementScreen } from '@screens/HR/LeaveManagementScreen';
import { ApplyLeaveScreen } from '@screens/HR/ApplyLeaveScreen';

const Stack = createStackNavigator();

export const MoreNavigator = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="MoreMenu" component={MoreScreen} />
        <Stack.Screen name="StaffDirectory" component={StaffDirectoryScreen} />
        <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
        <Stack.Screen name="StaffEdit" component={StaffEditScreen} />
        <Stack.Screen name="LeaveManagement" component={LeaveManagementScreen} />
        <Stack.Screen name="ApplyLeave" component={ApplyLeaveScreen} />
        <Stack.Screen name="PayrollRecords" component={PayrollRecordsScreen} />
        <Stack.Screen name="RoutesList" component={RoutesListScreen} />
        <Stack.Screen name="RouteDetail" component={RouteDetailScreen} />
        <Stack.Screen name="LibraryBooks" component={LibraryBooksScreen} />
        <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
        <Stack.Screen name="Notifications" component={NotificationsScreen} />
        <Stack.Screen name="ExamsList" component={ExamsListScreen} />
        <Stack.Screen name="ExamMarksSetup" component={ExamMarksSetupScreen} />
        <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
    </Stack.Navigator>
);
