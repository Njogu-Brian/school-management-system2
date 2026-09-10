import {
  formatRoleLabel,
  TEACHER_HOME_ACCOUNT_ACTIONS,
  TEACHER_HOME_CORE_ACTIONS,
  TEACHER_HOME_MORE_ACTIONS,
  timeOfDayGreeting,
  useAuth,
  useClassrooms,
  useCurrentUser,
  useUnreadNotificationCount,
  type TeacherHomeActionDef,
} from '@erp/core';
import {
  Button,
  DashboardHero,
  DashboardSection,
  EmptyState,
  QuickAction,
  ScreenContainer,
  SkeletonListRows,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, RefreshControl, Text, View } from 'react-native';
import { useQueryClient } from '@tanstack/react-query';
import { navigateToTab } from '../../../navigation/navigateToTab';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';
import { confirmAction } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;

function ActionGrid({
  actions,
  unread,
  onPress,
}: {
  actions: TeacherHomeActionDef[];
  unread?: number;
  onPress: (action: TeacherHomeActionDef) => void;
}) {
  const { spacing } = useTheme();
  return (
    <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
      {actions.map((action) => (
        <QuickAction
          key={action.id}
          label={
            action.id === 'notifications' && unread && unread > 0
              ? `Notifications (${unread})`
              : action.label
          }
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
  const { palette, spacing, typography, colors } = useTheme();
  const navigation = useNavigation<Nav>();
  const queryClient = useQueryClient();
  const classroomsQuery = useClassrooms();
  const unreadQuery = useUnreadNotificationCount();
  const [manualRefreshing, setManualRefreshing] = useState(false);

  const classTeacherCount = user?.classTeacherClassroomIds?.length ?? 0;
  const teachingClassCount = classroomsQuery.data?.length ?? 0;
  const roleLabel = formatRoleLabel(user?.roleName ?? user?.role, 'Teacher');
  const unread = unreadQuery.data ?? 0;
  const refreshing = manualRefreshing || classroomsQuery.isRefetching || unreadQuery.isRefetching;

  const meta = useMemo(() => {
    const parts: string[] = [];
    if (classTeacherCount > 0) parts.push(`Class teacher · ${classTeacherCount}`);
    if (teachingClassCount > 0) parts.push(`${teachingClassCount} classes`);
    if (unread > 0) parts.push(`${unread} unread`);
    return parts.join(' · ') || undefined;
  }, [classTeacherCount, teachingClassCount, unread]);

  const goTo = (action: TeacherHomeActionDef) => {
    navigateToTab(
      navigation,
      action.jump.tab,
      action.jump.screen,
      undefined,
      action.jump.tabHome,
    );
  };

  const onRefresh = async () => {
    setManualRefreshing(true);
    try {
      await Promise.all([
        classroomsQuery.refetch(),
        unreadQuery.refetch(),
        queryClient.invalidateQueries({ queryKey: ['notifications'] }),
        queryClient.invalidateQueries({ queryKey: ['assignments'] }),
        queryClient.invalidateQueries({ queryKey: ['diaries'] }),
      ]);
    } finally {
      setManualRefreshing(false);
    }
  };

  return (
    <ScreenContainer
      scroll
      edges={['bottom']}
      contentContainerStyle={{ padding: spacing.md }}
      scrollProps={{
        refreshControl: (
          <RefreshControl refreshing={refreshing} onRefresh={() => void onRefresh()} colors={[colors.primary]} />
        ),
      }}
    >
      {refreshing ? (
        <View style={{ alignItems: 'center', marginBottom: spacing.sm }}>
          <ActivityIndicator color={colors.primary} />
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
            Refreshing…
          </Text>
        </View>
      ) : null}

      <DashboardHero
        variant="academics"
        greeting={timeOfDayGreeting()}
        userName={user?.name ?? 'Teacher'}
        roleLabel={roleLabel}
        title="Home"
        subtitle="Today's teaching work — attendance, homework, marks, and messages"
        meta={meta}
      />

      <View style={{ marginBottom: spacing.md }}>
        <AppModeSwitch />
      </View>

      {classroomsQuery.isLoading ? (
        <SkeletonListRows count={2} />
      ) : classroomsQuery.isError ? (
        <EmptyState
          title="Could not load classes"
          message={classroomsQuery.error instanceof Error ? classroomsQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void classroomsQuery.refetch()}
        />
      ) : (
        <DashboardSection title="Your classes today">
          {(classroomsQuery.data ?? []).length === 0 ? (
            <EmptyState
              title="No classes in scope"
              message="Assigned classes will appear here when the school links you to classrooms."
              icon="people-outline"
            />
          ) : (
            (classroomsQuery.data ?? []).slice(0, 4).map((c) => (
              <SurfaceCard
                key={c.id}
                accent="brand"
                onPress={() => navigateToTab(navigation, 'Classes', 'ClassesMain')}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{c.name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  Tap to open classes
                </Text>
              </SurfaceCard>
            ))
          )}
        </DashboardSection>
      )}

      {unread > 0 ? (
        <DashboardSection title="Alerts">
          <SurfaceCard
            accent="warning"
            onPress={() =>
              navigateToTab(navigation, 'Home', 'Notifications', undefined, 'HomeMain')
            }
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
              {unread} unread notification{unread === 1 ? '' : 's'}
            </Text>
          </SurfaceCard>
        </DashboardSection>
      ) : null}

      <DashboardSection title="Daily work" subtitle="Core teaching tasks">
        <ActionGrid actions={TEACHER_HOME_CORE_ACTIONS} unread={unread} onPress={goTo} />
      </DashboardSection>

      <DashboardSection title="More tools" subtitle="Self-service and school tools">
        <ActionGrid actions={TEACHER_HOME_MORE_ACTIONS} onPress={goTo} />
      </DashboardSection>

      <DashboardSection title="Account">
        <ActionGrid actions={TEACHER_HOME_ACCOUNT_ACTIONS} onPress={goTo} />
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
