import { useCurrentUser, UserRole } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Pressable, Text } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const TeacherMoreHubScreen: React.FC = () => {
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const navigation = useNavigation<Nav>();

  const isSenior =
    user?.role === UserRole.SENIOR_TEACHER || user?.role === UserRole.SUPERVISOR;

  const items = useMemo(() => {
    const base: Array<{
      title: string;
      route: keyof TeacherStackParamList;
      icon:
        | 'time-outline'
        | 'calendar-outline'
        | 'wallet-outline'
        | 'person-outline'
        | 'bus-outline'
        | 'notifications-outline'
        | 'settings-outline'
        | 'checkmark-circle-outline'
        | 'megaphone-outline'
        | 'chatbubbles-outline'
        | 'list-outline'
        | 'cash-outline';
    }> = [
      { title: 'Clock in / out', route: 'StaffClock', icon: 'time-outline' },
      { title: 'My leave', route: 'MyLeaveList', icon: 'list-outline' },
      { title: 'Apply for leave', route: 'LeaveApply', icon: 'calendar-outline' },
      { title: 'Salary advances', route: 'MyAdvances', icon: 'cash-outline' },
      { title: 'My payslips', route: 'MyPayslips', icon: 'wallet-outline' },
      { title: 'My profile', route: 'MyProfile', icon: 'person-outline' },
      { title: 'Transport pickup', route: 'TeacherTransportHub', icon: 'bus-outline' },
      { title: 'Student diary', route: 'DiaryList', icon: 'chatbubbles-outline' },
      { title: 'Announcements', route: 'Announcements', icon: 'megaphone-outline' },
      { title: 'Notifications', route: 'Notifications', icon: 'notifications-outline' },
      { title: 'Settings', route: 'Settings', icon: 'settings-outline' },
    ];
    if (isSenior) {
      base.unshift({
        title: 'Lesson plan review',
        route: 'LessonPlanReview',
        icon: 'checkmark-circle-outline',
      });
    }
    return base;
  }, [isSenior]);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="More"
        subtitle="Self-service and extras"
        onProfilePress={() => navigation.navigate('MyProfile')}
      />
      {items.map((item) => (
        <Pressable
          key={`${item.route}-${item.title}`}
          onPress={() => navigation.navigate(item.route as never)}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: spacing.md,
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
            marginBottom: spacing.sm,
          }}
        >
          <Soft3DIcon name={item.icon} tone="cyan" size={40} />
          <Text style={{ color: palette.textPrimary, fontWeight: '600', flex: 1 }}>{item.title}</Text>
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>Open</Text>
        </Pressable>
      ))}
    </ScreenContainer>
  );
};
