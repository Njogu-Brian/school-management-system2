import { useApplicationDetail, type ApplicationSummary, type EnrolledStudentRecord } from '@erp/core';
import {
  Admissions360Layout,
  EmptyState,
  ScreenContainer,
  useTheme,
  type Admissions360TabId,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, View } from 'react-native';
import type { AdmissionsStackParamList } from '../../../navigation/admissionsStackTypes';
import { DocumentsTab } from '../application360/tabs/DocumentsTab';
import { EnrollmentTab } from '../application360/tabs/EnrollmentTab';
import { OverviewTab } from '../application360/tabs/OverviewTab';
import { ParentsTab } from '../application360/tabs/ParentsTab';
import { StudentTab } from '../application360/tabs/StudentTab';
import { TimelineTab } from '../application360/tabs/TimelineTab';

type Props = StackScreenProps<AdmissionsStackParamList, 'ApplicationDetail'>;

const TABS: Array<{ id: Admissions360TabId; label: string }> = [
  { id: 'overview', label: 'Overview' },
  { id: 'student', label: 'Student' },
  { id: 'parents', label: 'Parents' },
  { id: 'documents', label: 'Documents' },
  { id: 'timeline', label: 'Timeline' },
  { id: 'enrollment', label: 'Enrollment' },
];

export const ApplicationDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { applicationId, summary } = route.params;
  const { colors } = useTheme();
  const [activeTab, setActiveTab] = useState<Admissions360TabId>('overview');

  const detailQuery = useApplicationDetail(applicationId);

  const handleViewEnrolledStudent = (student: EnrolledStudentRecord) => {
    const drawerNav = navigation.getParent();
    drawerNav?.navigate('Workspace', {
      screen: 'Students',
      params: {
        screen: 'StudentDetail',
        params: {
          studentId: student.id,
          summary: {
            id: student.id,
            admissionNumber: student.admission_number,
            fullName: student.full_name,
            className: student.class_name,
            streamName: student.stream_name,
            classroomId: student.classroom_id,
            streamId: student.stream_id,
            gender: student.gender ?? '',
            enrollmentStatus: student.status,
            feeStatus: null,
            avatarUrl: student.photo_url,
            gradeLevel: null,
          },
        },
      },
    });
  };

  const header = useMemo(() => {
    const app = detailQuery.data;
    const seed = summary as ApplicationSummary | undefined;
    return {
      id: applicationId,
      fullName: app?.fullName ?? seed?.fullName ?? 'Application',
      applicationStatus: app?.applicationStatus ?? seed?.applicationStatus ?? 'pending',
      applicationDate: app?.applicationDate ?? seed?.applicationDate ?? null,
      preferredClassName: app?.preferredClassName ?? seed?.preferredClassName ?? null,
      avatarUrl: app?.passportPhotoUrl ?? seed?.passportPhotoUrl ?? null,
      waitlistPosition: app?.waitlistPosition ?? seed?.waitlistPosition ?? null,
    };
  }, [detailQuery.data, summary, applicationId]);

  const content = (() => {
    if (detailQuery.isLoading) {
      return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      );
    }
    if (detailQuery.isError || !detailQuery.data) {
      return (
        <EmptyState
          title="Could not load application"
          message={(detailQuery.error as Error)?.message ?? 'Something went wrong.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void detailQuery.refetch()}
        />
      );
    }

    const app = detailQuery.data;
    switch (activeTab) {
      case 'overview':
        return <OverviewTab application={app} />;
      case 'student':
        return <StudentTab application={app} />;
      case 'parents':
        return <ParentsTab application={app} />;
      case 'documents':
        return <DocumentsTab application={app} />;
      case 'timeline':
        return <TimelineTab application={app} />;
      case 'enrollment':
        return (
          <EnrollmentTab application={app} onViewStudent={handleViewEnrolledStudent} />
        );
      default:
        return null;
    }
  })();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <Admissions360Layout
        header={header}
        tabs={TABS}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        onBack={() => navigation.goBack()}
      >
        {content}
      </Admissions360Layout>
    </ScreenContainer>
  );
};
