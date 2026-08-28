import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import { SettingsScreen } from '../features/settings/screens/SettingsScreen';
import {
  AboutSettingsScreen,
  AcademicSettingsScreen,
  GradingSettingsScreen,
  RolesSettingsScreen,
  SchoolSettingsScreen,
  SessionSettingsScreen,
} from '../features/settings/screens/SettingsDetailScreens';
import type { SettingsStackParamList } from './settingsStackTypes';

const Stack = createStackNavigator<SettingsStackParamList>();

/** Settings hub + detail screens. Header and floating tabs stay visible. */
export const SettingsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="SettingsHub">
    <Stack.Screen name="SettingsHub" component={SettingsScreen} />
    <Stack.Screen name="SettingsSchool" component={SchoolSettingsScreen} />
    <Stack.Screen name="SettingsAcademic" component={AcademicSettingsScreen} />
    <Stack.Screen name="SettingsGrading" component={GradingSettingsScreen} />
    <Stack.Screen name="SettingsRoles" component={RolesSettingsScreen} />
    <Stack.Screen name="SettingsSession" component={SessionSettingsScreen} />
    <Stack.Screen name="SettingsAbout" component={AboutSettingsScreen} />
  </Stack.Navigator>
);
