import type { LeaveRequestRecord, StaffLeaveBalanceItem } from '@erp/core';
import { EmptyState, LeaveBalanceCards, LeaveRequestListItem, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

export interface LeaveTabProps {
  balances: StaffLeaveBalanceItem[];
  balancesLoading: boolean;
  balancesError: boolean;
  onRetryBalances?: () => void;
  requests: LeaveRequestRecord[];
  requestsLoading: boolean;
  requestsError: boolean;
  onRetryRequests?: () => void;
}

export const LeaveTab: React.FC<LeaveTabProps> = ({
  balances,
  balancesLoading,
  balancesError,
  onRetryBalances,
  requests,
  requestsLoading,
  requestsError,
  onRetryRequests,
}) => {
  const { palette, colors, spacing, typography } = useTheme();

  const balanceCards = useMemo(
    () =>
      balances.map((b) => ({
        id: b.id,
        leaveTypeName: b.leaveTypeName,
        remainingDays: b.remainingDays,
        usedDays: b.usedDays,
        entitlementDays: b.entitlementDays,
      })),
    [balances],
  );

  const requestItems = useMemo(
    () =>
      requests.map((r) => ({
        id: r.id,
        leaveTypeName: r.leave_type_name ?? r.leave_type ?? 'Leave',
        startDate: r.start_date,
        endDate: r.end_date,
        days: r.days_count ?? r.days ?? 0,
        status: r.status,
        reason: r.reason,
      })),
    [requests],
  );

  return (
    <View>
      <Text
        style={[
          styles.section,
          {
            color: palette.textMuted,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginBottom: spacing.sm,
          },
        ]}
      >
        Balances (active year)
      </Text>

      {balancesLoading ? (
        <ActivityIndicator color={colors.primary} />
      ) : balancesError ? (
        <EmptyState
          title="Could not load balances"
          message="Leave balances failed to load."
          icon="alert-circle-outline"
          actionLabel={onRetryBalances ? 'Retry' : undefined}
          onAction={onRetryBalances}
        />
      ) : (
        <LeaveBalanceCards balances={balanceCards} />
      )}

      <Text
        style={[
          styles.section,
          {
            color: palette.textMuted,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          },
        ]}
      >
        Leave history
      </Text>

      {requestsLoading ? (
        <ActivityIndicator color={colors.primary} />
      ) : requestsError ? (
        <EmptyState
          title="Could not load leave"
          message="Leave requests failed to load."
          icon="alert-circle-outline"
          actionLabel={onRetryRequests ? 'Retry' : undefined}
          onAction={onRetryRequests}
        />
      ) : requestItems.length === 0 ? (
        <EmptyState
          title="No leave requests"
          message="No leave requests on record."
          icon="calendar-outline"
        />
      ) : (
        requestItems.map((item) => <LeaveRequestListItem key={item.id} item={item} />)
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  section: { fontWeight: '700', textTransform: 'uppercase' },
});
