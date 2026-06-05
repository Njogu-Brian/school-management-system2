import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { SharedReportCardDetailScreen } from '../../shared/screens/ReportCardDetailScreen';

type Props = StackScreenProps<StudentsStackParamList, 'ReportCardDetail'>;

export const ReportCardDetailScreen: React.FC<Props> = ({ route, navigation }) => (
  <SharedReportCardDetailScreen
    reportCardId={route.params.reportCardId}
    studentName={route.params.studentName}
    onBack={() => navigation.goBack()}
  />
);
