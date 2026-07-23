import { useDriverBoarding, useDriverTrip, useDriverTripActions, useDriverTrips } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  DashboardHero,
  DashboardSection,
  EmptyState,
  QuickAction,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  StatusBadge,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { DriverStackParamList } from '../../../navigation/driver/driverStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';

type Nav = StackNavigationProp<DriverStackParamList>;

type HubLink = {
  title: string;
  subtitle: string;
  route: keyof DriverStackParamList;
  icon:
    | 'notifications-outline'
    | 'settings-outline'
    | 'time-outline'
    | 'person-outline'
    | 'map-outline'
    | 'home-outline'
    | 'calendar-outline'
    | 'list-outline'
    | 'wallet-outline'
    | 'cash-outline'
    | 'alert-circle-outline'
    | 'car-outline';
  tone: 'blue' | 'indigo' | 'cyan' | 'amber' | 'emerald' | 'rose';
};

export const DRIVER_HUB_LINKS: HubLink[] = [
  {
    title: 'Staff clock',
    subtitle: 'Clock in or out for your shift',
    route: 'StaffClock',
    icon: 'time-outline',
    tone: 'cyan',
  },
  {
    title: 'Apply for leave',
    subtitle: 'Submit a leave request',
    route: 'LeaveApply',
    icon: 'calendar-outline',
    tone: 'emerald',
  },
  {
    title: 'My leave',
    subtitle: 'Track leave requests',
    route: 'MyLeaveList',
    icon: 'list-outline',
    tone: 'indigo',
  },
  {
    title: 'My payslips',
    subtitle: 'Download salary slips',
    route: 'MyPayslips',
    icon: 'wallet-outline',
    tone: 'blue',
  },
  {
    title: 'Salary advances',
    subtitle: 'Request or view advances',
    route: 'MyAdvances',
    icon: 'cash-outline',
    tone: 'amber',
  },
  {
    title: 'My profile',
    subtitle: 'Contact details and account',
    route: 'MyProfile',
    icon: 'person-outline',
    tone: 'indigo',
  },
  {
    title: 'My vehicle',
    subtitle: 'Assigned transport details',
    route: 'DriverVehicle',
    icon: 'car-outline',
    tone: 'cyan',
  },
  {
    title: 'Notifications',
    subtitle: 'Trip alerts and school messages',
    route: 'Notifications',
    icon: 'notifications-outline',
    tone: 'blue',
  },
  {
    title: 'Raise a concern',
    subtitle: 'Flag an issue about a student',
    route: 'RaiseConcern',
    icon: 'alert-circle-outline',
    tone: 'rose',
  },
  {
    title: 'Concerns',
    subtitle: 'View concerns you have raised',
    route: 'ConcernsList',
    icon: 'alert-circle-outline',
    tone: 'rose',
  },
  {
    title: 'Settings',
    subtitle: 'Theme and sign out',
    route: 'DriverSettings',
    icon: 'settings-outline',
    tone: 'amber',
  },
];

function tripStatusTone(status?: string | null): 'success' | 'warning' | 'info' | 'brand' {
  switch (status) {
    case 'in_progress':
      return 'success';
    case 'completed':
      return 'info';
    case 'not_started':
    case 'scheduled':
      return 'warning';
    default:
      return 'brand';
  }
}

function formatTripStatus(status?: string | null): string {
  if (!status || status === 'not_started') return 'scheduled';
  return status.replace(/_/g, ' ');
}

function boardingTone(status?: string | null): 'success' | 'danger' | 'warning' | 'info' {
  switch (status) {
    case 'present':
      return 'success';
    case 'absent':
      return 'danger';
    case 'late':
      return 'warning';
    default:
      return 'info';
  }
}

function HubLinksList({ onNavigate }: { onNavigate: (route: keyof DriverStackParamList) => void }) {
  const { palette, spacing, typography, radius } = useTheme();
  return (
    <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
      {DRIVER_HUB_LINKS.map((item) => (
        <Pressable
          key={item.route}
          onPress={() => onNavigate(item.route)}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: spacing.md,
            backgroundColor: palette.surface,
            borderWidth: 1,
            borderColor: palette.border,
            borderRadius: radius.lg,
            padding: spacing.md,
          }}
        >
          <Soft3DIcon name={item.icon} tone={item.tone} size={40} />
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{item.subtitle}</Text>
          </View>
        </Pressable>
      ))}
    </View>
  );
}

const HOME_QUICK_ACTIONS: Array<{
  label: string;
  icon: 'car-outline' | 'time-outline' | 'notifications-outline' | 'alert-circle-outline';
  route: keyof DriverStackParamList;
}> = [
  { label: 'My vehicle', icon: 'car-outline', route: 'DriverVehicle' },
  { label: 'Staff clock', icon: 'time-outline', route: 'StaffClock' },
  { label: 'Notifications', icon: 'notifications-outline', route: 'Notifications' },
  { label: 'Raise concern', icon: 'alert-circle-outline', route: 'RaiseConcern' },
];

export const DriverHomeScreen: React.FC = () => {
  const { palette, spacing, typography, radius } = useTheme();
  const navigation = useNavigation<Nav>();
  const tabClearance = useFloatingTabBarClearance();
  const tripsQuery = useDriverTrips();

  const trips = tripsQuery.data ?? [];
  const meta = trips.length > 0 ? `${trips.length} trips today` : undefined;

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}>
      <DashboardHero
        variant="operations"
        greeting="Welcome back"
        title="Driver portal"
        subtitle="Today's roster, vehicle status, and self-service"
        meta={meta}
      />

      <DashboardSection title="Today's trips" subtitle="Start from your assigned roster">
        {tripsQuery.isLoading ? (
          <SkeletonListRows count={4} />
        ) : tripsQuery.isError ? (
          <EmptyState
            title="Could not load trips"
            message={tripsQuery.error instanceof Error ? tripsQuery.error.message : 'Try again later.'}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void tripsQuery.refetch()}
          />
        ) : trips.length === 0 ? (
          <EmptyState
            title="No trips today"
            message="Assigned trips for today will appear here when the school schedules them."
            icon="bus-outline"
          />
        ) : (
          trips.map((item) => (
            <Pressable
              key={item.id}
              onPress={() => navigation.navigate('TripDetail', { tripId: item.id })}
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>
                  {item.name ?? `Trip #${item.id}`}
                </Text>
                <StatusBadge label={formatTripStatus(item.status)} tone={tripStatusTone(item.status)} compact />
              </View>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {[item.direction, item.departure_time, item.vehicle_registration, item.student_count != null ? `${item.student_count} students` : null]
                  .filter(Boolean)
                  .join(' · ')}
              </Text>
            </Pressable>
          ))
        )}
      </DashboardSection>

      <DashboardSection title="Quick actions">
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {HOME_QUICK_ACTIONS.map((item) => (
            <QuickAction
              key={item.route}
              label={item.label}
              icon={item.icon}
              onPress={() => navigation.navigate(item.route as never)}
            />
          ))}
        </View>
      </DashboardSection>
    </ScreenContainer>
  );
};

export const DriverTripDetailScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteProp<DriverStackParamList, 'TripDetail'>>();
  const tripId = route.params.tripId;
  const { palette, spacing, typography, radius } = useTheme();
  const tripQuery = useDriverTrip(tripId);
  const boardingQuery = useDriverBoarding(tripId);
  const { start, stop } = useDriverTripActions(tripId);

  const trip = tripQuery.data;
  const students = trip?.students ?? [];
  const boardingByStudent = useMemo(() => {
    const map = new Map<number, string>();
    for (const row of boardingQuery.data?.students ?? []) {
      map.set(row.student_id, row.status);
    }
    return map;
  }, [boardingQuery.data?.students]);
  const status = trip?.status ?? 'not_started';
  const inProgress = status === 'in_progress';
  const completed = status === 'completed';
  const canStart = !inProgress && !completed;

  const handleStart = () => {
    confirmAction('Start trip', 'Begin this trip and enable live location sharing?', 'Start trip', async () => {
      try {
        await start.mutateAsync();
        showSuccess('Trip started', 'You can open the active trip screen to share GPS.');
        void tripQuery.refetch();
        navigation.navigate('ActiveTrip', { tripId });
      } catch (err) {
        showError('Could not start trip', err instanceof Error ? err.message : 'Try again.');
      }
    });
  };

  const handleStop = () => {
    confirmAction('End trip', 'Mark this trip as completed?', 'End trip', async () => {
      try {
        await stop.mutateAsync();
        showSuccess('Trip ended', 'Trip marked as completed.');
        void tripQuery.refetch();
      } catch (err) {
        showError('Could not end trip', err instanceof Error ? err.message : 'Try again.');
      }
    });
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Trip roster" onBack={() => navigation.goBack()} />
      {tripQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : tripQuery.isError ? (
        <EmptyState
          title="Could not load trip"
          message={tripQuery.error instanceof Error ? tripQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Back to trips"
          onAction={() => navigation.navigate('DriverHomeMain')}
        />
      ) : (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.xs }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.headline.fontSize, flex: 1 }}>
              {trip?.name ?? `Trip #${tripId}`}
            </Text>
            <StatusBadge label={formatTripStatus(status)} tone={tripStatusTone(status)} compact />
          </View>
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
            {[trip?.direction, trip?.departure_time, trip?.vehicle_registration].filter(Boolean).join(' · ')}
          </Text>

          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md }}>
            {canStart ? (
              <Button
                label="Start trip"
                variant="primary"
                loading={start.isPending}
                onPress={handleStart}
                style={{ flexGrow: 1 }}
              />
            ) : null}
            {inProgress ? (
              <>
                <Button
                  label="Open active trip"
                  variant="primary"
                  onPress={() => navigation.navigate('ActiveTrip', { tripId })}
                  style={{ flexGrow: 1 }}
                />
                <Button
                  label="Boarding checklist"
                  variant="secondary"
                  onPress={() => navigation.navigate('BoardingChecklist', { tripId })}
                  style={{ flexGrow: 1 }}
                />
                <Button
                  label="End trip"
                  variant="secondary"
                  loading={stop.isPending}
                  onPress={handleStop}
                  style={{ flexGrow: 1 }}
                />
              </>
            ) : null}
            {!inProgress && canStart ? (
              <Button
                label="Boarding checklist"
                variant="secondary"
                onPress={() => navigation.navigate('BoardingChecklist', { tripId })}
                style={{ flexGrow: 1 }}
              />
            ) : null}
            {(inProgress || completed) ? (
              <Button
                label="Boarding checklist"
                variant="secondary"
                onPress={() => navigation.navigate('BoardingChecklist', { tripId })}
                style={{ flexGrow: 1 }}
              />
            ) : null}
          </View>

          {students.length === 0 ? (
            <EmptyState
              title="No students on roster"
              message="Students assigned to this trip will list here."
              icon="people-outline"
            />
          ) : (
            students.map((s) => {
              const boardingStatus = boardingByStudent.get(s.id);
              return (
              <View
                key={s.id}
                style={{
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '600', flex: 1 }}>{s.full_name}</Text>
                  {boardingStatus ? (
                    <StatusBadge label={boardingStatus} tone={boardingTone(boardingStatus)} compact />
                  ) : (
                    <StatusBadge label="pending" tone="warning" compact />
                  )}
                </View>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[s.admission_number, s.drop_point, s.fee_status].filter(Boolean).join(' · ')}
                </Text>
              </View>
            );
            })
          )}
        </>
      )}
    </ScreenContainer>
  );
};

export const DriverRoutesScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { spacing } = useTheme();

  const openHomeTab = () => {
    navigation.getParent()?.navigate('DriverHomeTab' as never);
  };

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Routes" subtitle="Use Home for today's assigned trips" />
      <EmptyState
        title="Route map coming soon"
        message="Your assigned routes will list here. Open Home to see today's trip roster."
        icon="map-outline"
        actionLabel="Today's trips"
        onAction={openHomeTab}
      />
      <HubLinksList onNavigate={(route) => navigation.navigate(route as never)} />
    </ScreenContainer>
  );
};

export const DriverMoreHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { spacing } = useTheme();

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Account"
        subtitle="HR, vehicle, and settings"
        onProfilePress={() => navigation.navigate('MyProfile')}
      />
      <AppModeSwitch style={{ marginBottom: spacing.md }} />
      <HubLinksList onNavigate={(route) => navigation.navigate(route as never)} />
    </ScreenContainer>
  );
};
