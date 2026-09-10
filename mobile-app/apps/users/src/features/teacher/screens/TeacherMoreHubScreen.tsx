import { useAuth, useCurrentUser, UserRole } from '@erp/core';
import { Button, ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';
import { confirmAction } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;

/** Category B/C/D — secondary, rare/admin, and settings (core daily work lives on Home). */
export const TeacherMoreHubScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const navigation = useNavigation<Nav>();

  const isSenior =
    user?.role === UserRole.SENIOR_TEACHER || user?.role === UserRole.SUPERVISOR;

  const items = useMemo(() => {
    const base: Array<{
      title: string;
      subtitle: string;
      route: keyof TeacherStackParamList;
      icon:
        | 'time-outline'
        | 'calendar-outline'
        | 'wallet-outline'
        | 'person-outline'
        | 'notifications-outline'
        | 'settings-outline'
        | 'checkmark-circle-outline'
        | 'megaphone-outline'
        | 'list-outline'
        | 'alert-circle-outline'
        | 'book-outline'
        | 'cash-outline'
        | 'clipboard-outline';
    }> = [
      { title: 'Academics hub', subtitle: 'All academic tools', route: 'Academics', icon: 'book-outline' },
      { title: 'Collect requirements', subtitle: 'Class requirements', route: 'RequirementsHub', icon: 'clipboard-outline' },
      { title: 'My attendance', subtitle: 'Staff clock in/out', route: 'StaffClock', icon: 'time-outline' },
      { title: 'My leave', subtitle: 'Leave history', route: 'MyLeaveList', icon: 'list-outline' },
      { title: 'Apply for leave', subtitle: 'New leave request', route: 'LeaveApply', icon: 'calendar-outline' },
      { title: 'Salary advances', subtitle: 'Request or track', route: 'MyAdvances', icon: 'cash-outline' },
      { title: 'My payslips', subtitle: 'Payroll documents', route: 'MyPayslips', icon: 'wallet-outline' },
      { title: 'Announcements', subtitle: 'School notices', route: 'Announcements', icon: 'megaphone-outline' },
      { title: 'Raise a concern', subtitle: 'Flag an issue', route: 'RaiseConcern', icon: 'alert-circle-outline' },
      { title: 'Concerns', subtitle: 'Your concern list', route: 'ConcernsList', icon: 'alert-circle-outline' },
      { title: 'My profile', subtitle: 'Account details', route: 'MyProfile', icon: 'person-outline' },
      { title: 'Settings', subtitle: 'Theme and security', route: 'Settings', icon: 'settings-outline' },
    ];
    if (isSenior) {
      base.unshift({
        title: 'Lesson plan review',
        subtitle: 'Approve supervised plans',
        route: 'LessonPlanReview',
        icon: 'checkmark-circle-outline',
      });
    }
    return base;
  }, [isSenior]);

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <View style={{ marginBottom: spacing.md }}>
        <AppModeSwitch />
      </View>
      <Text style={{ color: palette.textMuted, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
        Daily teaching tools are on Home. This menu holds secondary and self-service items.
      </Text>
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
            minHeight: 56,
          }}
        >
          <Soft3DIcon name={item.icon} size={44} />
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
              {item.title}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{item.subtitle}</Text>
          </View>
        </Pressable>
      ))}
      <Button
        label="Sign out"
        variant="ghost"
        onPress={() =>
          confirmAction('Sign out', 'Sign out of the Users app on this device?', 'Sign out', () => void logout(), true)
        }
        style={{ marginTop: spacing.md, borderColor: colors.error, borderWidth: 1 }}
      />
    </ScreenContainer>
  );
};
