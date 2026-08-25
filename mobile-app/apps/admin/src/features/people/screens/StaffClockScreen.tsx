import { useCan, useStaffClockHistory, useStaffClockRoster, useStaffClockToday } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  StatusBadge,
  useTheme,
  type SemanticTone,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'StaffClock'>;

function formatClockTime(value: string | null | undefined): string {
  if (!value) return '—';
  return value.slice(0, 5);
}

function formatClockDate(value: string | null | undefined): string {
  if (!value) return '—';
  const d = new Date(`${value}T00:00:00`);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
}

function statusTone(status: string): SemanticTone {
  const s = status.toLowerCase();
  if (s === 'present') return 'success';
  if (s === 'late' || s === 'half_day') return 'warning';
  if (s === 'absent') return 'danger';
  return 'info';
}

export const StaffClockScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const canViewTeam = useCan('staff.view');
  const todayQuery = useStaffClockToday();
  const historyQuery = useStaffClockHistory();
  const rosterQuery = useStaffClockRoster({ enabled: canViewTeam });
  const history = historyQuery.data ?? [];
  const loading = todayQuery.isLoading && !todayQuery.data;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={todayQuery.isRefetching || historyQuery.isRefetching}
            onRefresh={() => {
              void todayQuery.refetch();
              void historyQuery.refetch();
            }}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader
          title="Staff attendance"
          subtitle="Live records from BioTime fingerprint and card machines"
          onBack={() => navigation.goBack()}
        />
        {canViewTeam ? (
          <>
            <Button
              label="Team attendance records"
              variant="secondary"
              onPress={() => navigation.navigate('StaffClockTeam')}
              style={{ marginBottom: spacing.sm }}
            />
            {rosterQuery.isSuccess && (rosterQuery.data?.length ?? 0) > 0 ? (
              <Text style={{ color: palette.textSecondary, fontSize: 12, marginBottom: spacing.md }}>
                View last 90 days of gate records for {(rosterQuery.data ?? []).length} staff member(s).
              </Text>
            ) : null}
          </>
        ) : null}

        {loading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : (
          <>
            <FinanceFieldSection
              title="Today"
              rows={[
                { label: 'Status', value: todayQuery.data?.status?.replace(/_/g, ' ') ?? 'No record yet' },
                { label: 'Check in', value: formatClockTime(todayQuery.data?.check_in_time) },
                { label: 'Check out', value: formatClockTime(todayQuery.data?.check_out_time) },
              ]}
            />
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.sm,
                marginBottom: spacing.lg,
              }}
            >
              Staff sign in at the campus gates. GPS clock-in is turned off.
            </Text>

            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
              Recent attendance
            </Text>
            {history.length === 0 ? (
              <EmptyState
                title="No gate records yet"
                message="K40 punches will appear here after BioTime is synced to the ERP."
                icon="time-outline"
              />
            ) : (
              history.map((row) => (
                <View
                  key={row.id}
                  style={{
                    backgroundColor: palette.surface,
                    borderColor: palette.border,
                    borderWidth: 1,
                    borderRadius: radius.lg,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                  }}
                >
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                      {formatClockDate(row.date)}
                    </Text>
                    <StatusBadge
                      label={row.status.replace(/_/g, ' ')}
                      tone={statusTone(row.status)}
                      compact
                    />
                  </View>
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontSize: typography.caption.fontSize,
                      marginTop: 6,
                    }}
                  >
                    {formatClockTime(row.check_in_time)} → {formatClockTime(row.check_out_time)}
                  </Text>
                </View>
              ))
            )}
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
