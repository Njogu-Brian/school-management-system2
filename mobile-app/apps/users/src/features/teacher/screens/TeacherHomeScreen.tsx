import { formatRoleLabel, timeOfDayGreeting, useAuth, useClassrooms, useCurrentUser, useUnreadNotificationCount } from '@erp/core';
import {
  Button,
  DashboardHero,
  DashboardSection,
  QuickAction,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { View } from 'react-native';
import { navigateToTab } from '../../../navigation/navigateToTab';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { confirmAction } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;

type Action = {
  label: string;
  icon: React.ComponentProps<typeof QuickAction>['icon'];
  route: keyof TeacherStackParamList | 'Classes';
  /** Prefer jumping to the matching bottom tab so the bar highlight stays in sync. */
  tabJump?: { tab: string; screen: string; tabHome?: string };
};

const CLASS_TEACHER: Action[] = [
  { label: 'Mark attendance', icon: 'checkbox-outline', route: 'MarkAttendance', tabJump: { tab: 'Attendance', screen: 'AttendanceMain' } },
  { label: 'Collect requirements', icon: 'clipboard-outline', route: 'RequirementsHub', tabJump: { tab: 'More', screen: 'RequirementsHub', tabHome: 'MoreMain' } },
  { label: 'My students', icon: 'people-outline', route: 'Classes', tabJump: { tab: 'Classes', screen: 'ClassesMain' } },
  { label: 'Transport', icon: 'bus-outline', route: 'TeacherTransportHub', tabJump: { tab: 'More', screen: 'TeacherTransportHub', tabHome: 'MoreMain' } },
];

const TEACHING: Action[] = [
  { label: 'Enter marks', icon: 'create-outline', route: 'MarksHub', tabJump: { tab: 'More', screen: 'MarksHub', tabHome: 'MoreMain' } },
  { label: 'Student diary', icon: 'chatbubbles-outline', route: 'DiaryList', tabJump: { tab: 'More', screen: 'DiaryList', tabHome: 'MoreMain' } },
  { label: 'Homework', icon: 'book-outline', route: 'AssignmentsHub', tabJump: { tab: 'More', screen: 'AssignmentsHub', tabHome: 'MoreMain' } },
  { label: 'Lesson plans', icon: 'document-text-outline', route: 'LessonPlansHub', tabJump: { tab: 'More', screen: 'LessonPlansHub', tabHome: 'MoreMain' } },
];

const SELF_SERVICE: Action[] = [
  { label: 'My attendance', icon: 'time-outline', route: 'StaffClock', tabJump: { tab: 'More', screen: 'StaffClock', tabHome: 'MoreMain' } },
  { label: 'My leave', icon: 'calendar-outline', route: 'MyLeaveList', tabJump: { tab: 'More', screen: 'MyLeaveList', tabHome: 'MoreMain' } },
  { label: 'Advances', icon: 'cash-outline', route: 'MyAdvances', tabJump: { tab: 'More', screen: 'MyAdvances', tabHome: 'MoreMain' } },
  { label: 'Payslips', icon: 'wallet-outline', route: 'MyPayslips', tabJump: { tab: 'More', screen: 'MyPayslips', tabHome: 'MoreMain' } },
];

const SCHOOL: Action[] = [
  { label: 'Announcements', icon: 'megaphone-outline', route: 'Announcements', tabJump: { tab: 'More', screen: 'Announcements', tabHome: 'MoreMain' } },
  { label: 'Notifications', icon: 'notifications-outline', route: 'Notifications', tabJump: { tab: 'More', screen: 'Notifications', tabHome: 'MoreMain' } },
  { label: 'Raise concern', icon: 'alert-circle-outline', route: 'RaiseConcern', tabJump: { tab: 'More', screen: 'RaiseConcern', tabHome: 'MoreMain' } },
];

function ActionGrid({
  actions,
  onPress,
}: {
  actions: Action[];
  onPress: (action: Action) => void;
}) {
  const { spacing } = useTheme();
  return (
    <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
      {actions.map((action) => (
        <QuickAction
          key={action.route}
          label={action.label}
          icon={action.icon}
          onPress={() => onPress(action)}
        />
      ))}
    </View>
  );
}

export const TeacherHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { spacing, colors } = useTheme();
  const navigation = useNavigation<Nav>();
  const classroomsQuery = useClassrooms();
  const unreadQuery = useUnreadNotificationCount();

  const classTeacherCount = user?.classTeacherClassroomIds?.length ?? 0;
  const teachingClassCount = classroomsQuery.data?.length ?? 0;
  const roleLabel = formatRoleLabel(user?.roleName ?? user?.role, 'Teacher');

  const meta = useMemo(() => {
    const parts: string[] = [];
    if (classTeacherCount > 0) parts.push(`Class teacher of ${classTeacherCount}`);
    if (teachingClassCount > 0) parts.push(`${teachingClassCount} classes in scope`);
    const unread = unreadQuery.data ?? 0;
    if (unread > 0) parts.push(`${unread} unread`);
    return parts.join(' · ') || undefined;
  }, [classTeacherCount, teachingClassCount, unreadQuery.data]);

  const goTo = (action: Action) => {
    if (action.tabJump) {
      navigateToTab(
        navigation,
        action.tabJump.tab,
        action.tabJump.screen,
        undefined,
        action.tabJump.tabHome,
      );
      return;
    }
    navigation.navigate(action.route as never);
  };

  return (
    <ScreenContainer
      scroll
      edges={['bottom']}
      contentContainerStyle={{ padding: spacing.md }}
    >
      <DashboardHero
        variant="academics"
        greeting={timeOfDayGreeting()}
        userName={user?.name ?? 'Teacher'}
        roleLabel={roleLabel}
        title="Home"
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

      <Button
        label="Sign out"
        variant="ghost"
        onPress={() =>
          confirmAction('Sign out', 'Sign out of the Users app on this device?', 'Sign out', () => void logout(), true)
        }
        style={{ marginTop: spacing.md, marginBottom: spacing.sm, borderColor: colors.error, borderWidth: 1 }}
      />
    </ScreenContainer>
  );
};
