import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  ArchivedStudentsScreen,
  AttendanceReportScreen,
  MedicalRecordFormScreen,
  ParentsContactScreen,
  ReportCardDetailScreen,
  StudentDetailScreen,
  StudentEditScreen,
  StudentRegistryScreen,
} from '../features/students';
import type { StudentsStackParamList } from './studentsStackTypes';

const Stack = createStackNavigator<StudentsStackParamList>();

export const StudentsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }}>
    <Stack.Screen name="StudentRegistry" component={StudentRegistryScreen} />
    <Stack.Screen name="ParentsContact" component={ParentsContactScreen} />
    <Stack.Screen name="ArchivedStudents" component={ArchivedStudentsScreen} />
    <Stack.Screen name="AttendanceReport" component={AttendanceReportScreen} />
    <Stack.Screen name="StudentDetail" component={StudentDetailScreen} />
    <Stack.Screen name="ReportCardDetail" component={ReportCardDetailScreen} />
    <Stack.Screen name="MedicalRecordForm" component={MedicalRecordFormScreen} />
    <Stack.Screen name="StudentEdit" component={StudentEditScreen} />
  </Stack.Navigator>
);
