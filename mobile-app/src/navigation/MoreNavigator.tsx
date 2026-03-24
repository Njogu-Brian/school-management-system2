import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { MoreScreen } from '@screens/More/MoreScreen';
import { StaffDirectoryScreen } from '@screens/HR/StaffDirectoryScreen';
import { RoutesListScreen } from '@screens/Transport/RoutesListScreen';
import { LibraryBooksScreen } from '@screens/Library/LibraryBooksScreen';
import { AnnouncementsScreen } from '@screens/Communication/AnnouncementsScreen';
import { NotificationsScreen } from '@screens/Communication/NotificationsScreen';
import { StaffDetailScreen } from '@screens/HR/StaffDetailScreen';
import { ExamsListScreen } from '@screens/Academics/ExamsListScreen';
import { ExamMarksSetupScreen } from '@screens/Academics/ExamMarksSetupScreen';
import { MarksEntryScreen } from '@screens/Academics/MarksEntryScreen';

const Stack = createStackNavigator();

export const MoreNavigator = () => (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="MoreMenu" component={MoreScreen} />
        <Stack.Screen name="StaffDirectory" component={StaffDirectoryScreen} />
        <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
        <Stack.Screen name="RoutesList" component={RoutesListScreen} />
        <Stack.Screen name="LibraryBooks" component={LibraryBooksScreen} />
        <Stack.Screen name="Announcements" component={AnnouncementsScreen} />
        <Stack.Screen name="Notifications" component={NotificationsScreen} />
        <Stack.Screen name="ExamsList" component={ExamsListScreen} />
        <Stack.Screen name="ExamMarksSetup" component={ExamMarksSetupScreen} />
        <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
    </Stack.Navigator>
);
