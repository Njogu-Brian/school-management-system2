import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { SharedReportCardDetailScreen } from '../../shared/screens/ReportCardDetailScreen';

type Props = StackScreenProps<AcademicsStackParamList, 'ReportCardDetail'>;

export const ReportCardDetailScreen: React.FC<Props> = ({ route, navigation }) => (
  <SharedReportCardDetailScreen
    reportCardId={route.params.reportCardId}
    studentName={route.params.studentName}
    onBack={() => navigation.goBack()}
  />
);
