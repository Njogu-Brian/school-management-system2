import { createStackNavigator } from '@react-navigation/stack';
import React from 'react';
import {
  AcademicsDashboardScreen,
  AssessmentDetailScreen,
  CbcCurriculumScreen,
  CbcStrandsScreen,
  CbcSubstrandScreen,
  AssessmentHistoryScreen,
  AssessmentsScreen,
  ExamDetailScreen,
  ExamsListScreen,
  MarksEntryScreen,
  LessonPlanReviewScreen,
  MarksMatrixScreen,
  MarksMatrixSetupScreen,
  MarksMatrixEntryScreen,
  MarksScreen,
  ModerationScreen,
  ReportCardDetailScreen,
  ReportCardHistoryScreen,
  ReportCardsScreen,
} from '../features/academics';
import type { AcademicsStackParamList } from './academicsStackTypes';

const Stack = createStackNavigator<AcademicsStackParamList>();

export const AcademicsStackNavigator: React.FC = () => (
  <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="AcademicsDashboard">
    <Stack.Screen name="AcademicsDashboard" component={AcademicsDashboardScreen} />
    <Stack.Screen name="Assessments" component={AssessmentsScreen} />
    <Stack.Screen name="AssessmentHistory" component={AssessmentHistoryScreen} />
    <Stack.Screen name="AssessmentDetail" component={AssessmentDetailScreen} />
    <Stack.Screen name="ExamsList" component={ExamsListScreen} />
    <Stack.Screen name="ExamDetail" component={ExamDetailScreen} />
    <Stack.Screen name="MarksEntry" component={MarksEntryScreen} />
    <Stack.Screen name="Marks" component={MarksScreen} />
    <Stack.Screen name="MarksMatrix" component={MarksMatrixScreen} />
    <Stack.Screen name="MarksMatrixSetup" component={MarksMatrixSetupScreen} />
    <Stack.Screen name="MarksMatrixEntry" component={MarksMatrixEntryScreen} />
    <Stack.Screen name="ReportCards" component={ReportCardsScreen} />
    <Stack.Screen name="ReportCardHistory" component={ReportCardHistoryScreen} />
    <Stack.Screen name="ReportCardDetail" component={ReportCardDetailScreen} />
    <Stack.Screen name="Moderation" component={ModerationScreen} />
    <Stack.Screen name="LessonPlanReview" component={LessonPlanReviewScreen} />
    <Stack.Screen name="CbcCurriculum" component={CbcCurriculumScreen} />
    <Stack.Screen name="CbcStrands" component={CbcStrandsScreen} />
    <Stack.Screen name="CbcSubstrand" component={CbcSubstrandScreen} />
  </Stack.Navigator>
);
