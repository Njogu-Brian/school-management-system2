import { useClassrooms, useCurrentUser, useUnreadNotificationCount } from '@erp/core';
import {
  DashboardHero,
  DashboardSection,
  QuickAction,
  ScreenContainer,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

type Action = {
  label: string;
  icon: React.ComponentProps<typeof QuickAction>['icon'];
  route: keyof TeacherStackParamList | 'Classes';
};

const CLASS_TEACHER: Action[] = [
  { label: 'Mark attendance', icon: 'checkbox-outline', route: 'MarkAttendance' },
  { label: 'My students', icon: 'people-outline', route: 'Classes' },
  { label: 'Transport', icon: 'bus-outline', route: 'TeacherTransportHub' },
];

const TEACHING: Action[] = [
  { label: 'Enter marks', icon: 'create-outline', route: 'MarksHub' },
  { label: 'Student diary', icon: 'chatbubbles-outline', route: 'DiaryList' },
  { label: 'Homework', icon: 'book-outline', route: 'AssignmentsHub' },
  { label: 'Lesson plans', icon: 'document-text-outline', route: 'LessonPlansHub' },
];

const SELF_SERVICE: Action[] = [
  { label: 'Clock in/out', icon: 'time-outline', route: 'StaffClock' },
  { label: 'My leave', icon: 'calendar-outline', route: 'MyLeaveList' },
  { label: 'Advances', icon: 'cash-outline', route: 'MyAdvances' },
  { label: 'Payslips', icon: 'wallet-outline', route: 'MyPayslips' },
];

const SCHOOL: Action[] = [
  { label: 'Announcements', icon: 'megaphone-outline', route: 'Announcements' },
  { label: 'Notifications', icon: 'notifications-outline', route: 'Notifications' },
  { label: 'Raise concern', icon: 'alert-circle-outline', route: 'RaiseConcern' },
];

function ActionGrid({
  actions,
  onPress,
}: {
  actions: Action[];
  onPress: (route: Action['route']) => void;
}) {
  const { spacing } = useTheme();
  return (
    <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
      {actions.map((action) => (
        <QuickAction
          key={action.route}
          label={action.label}
          icon={action.icon}
          onPress={() => onPress(action.route)}
        />
      ))}
    </View>
  );
}

export const TeacherHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { spacing } = useTheme();
  const navigation = useNavigation<Nav>();
  const tabClearance = useFloatingTabBarClearance();
  const classroomsQuery = useClassrooms();
  const unreadQuery = useUnreadNotificationCount();

  const classTeacherCount = user?.classTeacherClassroomIds?.length ?? 0;
  const teachingClassCount = classroomsQuery.data?.length ?? 0;

  const meta = useMemo(() => {
    const parts: string[] = [];
    if (classTeacherCount > 0) parts.push(`Class teacher of ${classTeacherCount}`);
    if (teachingClassCount > 0) parts.push(`${teachingClassCount} classes in scope`);
    const unread = unreadQuery.data ?? 0;
    if (unread > 0) parts.push(`${unread} unread`);
    return parts.join(' · ') || undefined;
  }, [classTeacherCount, teachingClassCount, unreadQuery.data]);

  const goTo = (route: Action['route']) => navigation.navigate(route as never);

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}
    >
      <DashboardHero
        variant="academics"
        greeting="Welcome back"
        userName={user?.name ?? 'Teacher'}
        title={user?.roleName ?? 'Teacher'}
        subtitle="Today's capture, teaching, and self-service in one place"
        meta={meta}
      />

      <DashboardSection title="Class teacher" subtitle="Attendance, students, and transport for your homeroom">
        <ActionGrid actions={CLASS_TEACHER} onPress={goTo} />
      </DashboardSection>

      <DashboardSection title="Teaching" subtitle="Subjects you teach">
        <ActionGrid actions={TEACHING} onPress={goTo} />
      </DashboardSection>

      <DashboardSection title="Self-service" subtitle="HR and payroll shortcuts">
        <ActionGrid actions={SELF_SERVICE} onPress={goTo} />
      </DashboardSection>

      <DashboardSection title="School">
        <ActionGrid actions={SCHOOL} onPress={goTo} />
      </DashboardSection>
    </ScreenContainer>
  );
};
