import { useStaffClockHistory, useStaffClockToday } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  StatusBadge,
  useTheme,
  type SemanticTone,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, Text, View } from 'react-native';

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

export const StaffClockScreen: React.FC = () => {
  const navigation = useNavigation();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const todayQuery = useStaffClockToday();
  const historyQuery = useStaffClockHistory();

  const loading = todayQuery.isLoading && !todayQuery.data;
  const history = historyQuery.data ?? [];

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
          title="Sign in / out"
          subtitle="Records from the school fingerprint and card machines"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />

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
              Sign in and out at the campus gate. GPS clock-in is turned off. If a punch is missing, ask HR to
              correct it.
            </Text>

            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
              Recent attendance
            </Text>
            {historyQuery.isError ? (
              <EmptyState
                title="Could not load attendance"
                message={(historyQuery.error as Error)?.message ?? 'Try again in a moment.'}
                icon="alert-circle-outline"
                actionLabel="Retry"
                onAction={() => void historyQuery.refetch()}
              />
            ) : history.length === 0 ? (
              <EmptyState
                title="No gate records yet"
                message="When you sign in on a K40, your check-in and check-out will appear here."
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
                  <Text
                    style={{
                      color: palette.textMuted,
                      fontSize: typography.caption.fontSize,
                      marginTop: 4,
                    }}
                  >
                    School gate
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
