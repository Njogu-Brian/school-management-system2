import { useLiveBusForStudent, useStudentDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useMemo } from 'react';
import { Linking, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError } from '../../shared/utils/feedback';

type Route = RouteProp<ParentStackParamList, 'LiveBusTrack'>;

function liveMessage(data: { live?: boolean; status?: string | null; message?: string | null } | undefined): {
  label: string;
  tone: 'success' | 'warning' | 'info';
  detail: string;
} {
  if (!data) {
    return { label: 'Loading', tone: 'info', detail: 'Fetching bus location…' };
  }
  if (data.live) {
    return {
      label: 'Live',
      tone: 'success',
      detail: data.message ?? 'Bus is sharing live location.',
    };
  }
  if (data.status === 'completed') {
    return {
      label: 'Completed',
      tone: 'info',
      detail: data.message ?? 'Today\'s trip has finished.',
    };
  }
  return {
    label: 'Not started',
    tone: 'warning',
    detail: data.message ?? 'The bus has not started sharing location yet.',
  };
}

export const LiveBusTrackScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const studentId = route.params.studentId;
  const { palette, spacing, typography, radius } = useTheme();
  const student = useStudentDetail(studentId, { enabled: studentId > 0 });
  const liveQuery = useLiveBusForStudent(studentId, {
    enabled: studentId > 0,
    refetchInterval: 5_000,
  });

  const live = liveQuery.data;
  const statusInfo = useMemo(() => liveMessage(live), [live]);
  const hasCoords = live?.latitude != null && live?.longitude != null;

  const openMaps = async () => {
    if (!hasCoords) {
      showError('No location', 'The bus location is not available yet.');
      return;
    }
    const url = `https://www.google.com/maps?q=${live!.latitude},${live!.longitude}`;
    const supported = await Linking.canOpenURL(url);
    if (!supported) {
      showError('Cannot open maps', 'No maps app is available on this device.');
      return;
    }
    await Linking.openURL(url);
  };

  if (studentId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Track bus" onBack={() => navigation.goBack()} />
        <EmptyState title="Missing student" message="No child was selected." icon="alert-circle-outline" />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Track bus"
        subtitle={student.data?.fullName ?? (student.isLoading ? 'Loading…' : `Student #${studentId}`)}
        onBack={() => navigation.goBack()}
      />

      {liveQuery.isLoading ? (
        <SkeletonListRows count={4} />
      ) : liveQuery.isError ? (
        <EmptyState
          title="Could not load bus"
          message={liveQuery.error instanceof Error ? liveQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void liveQuery.refetch()}
        />
      ) : (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.md }}>
            <StatusBadge label={statusInfo.label} tone={statusInfo.tone} />
            {live?.age_seconds != null ? (
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                Updated {live.age_seconds}s ago
              </Text>
            ) : null}
          </View>

          <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }}>{statusInfo.detail}</Text>

          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.md,
              gap: spacing.sm,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
              {live?.trip_name ?? 'School bus'}
            </Text>
            {live?.direction ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                Direction: {live.direction}
              </Text>
            ) : null}
            {live?.vehicle_registration ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                Vehicle: {live.vehicle_registration}
              </Text>
            ) : null}
            {live?.driver_name ? (
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                Driver: {live.driver_name}
              </Text>
            ) : null}
            {hasCoords ? (
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                {live!.latitude!.toFixed(5)}, {live!.longitude!.toFixed(5)}
              </Text>
            ) : null}
          </View>

          <Button
            label="Open in Google Maps"
            variant="primary"
            disabled={!hasCoords}
            onPress={() => void openMaps()}
          />
        </>
      )}
    </ScreenContainer>
  );
};
