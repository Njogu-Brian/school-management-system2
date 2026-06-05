import type { LeaveRequestRecord, StaffLeaveBalanceItem } from '@erp/core';
import { LeaveBalanceCards, LeaveRequestListItem } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

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
  const { palette, colors, spacing, fontSizes } = useTheme();

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
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm },
        ]}
      >
        Balances (active year)
      </Text>

      {balancesLoading ? (
        <ActivityIndicator color={colors.primary} />
      ) : balancesError ? (
        <RetryBlock message="Could not load leave balances." onRetry={onRetryBalances} />
      ) : (
        <LeaveBalanceCards balances={balanceCards} />
      )}

      <Text
        style={[
          styles.section,
          {
            color: palette.textSecondary,
            fontSize: fontSizes.xs,
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
        <RetryBlock message="Could not load leave requests." onRetry={onRetryRequests} />
      ) : requestItems.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
          No leave requests on record.
        </Text>
      ) : (
        requestItems.map((item) => <LeaveRequestListItem key={item.id} item={item} />)
      )}
    </View>
  );
};

function RetryBlock({ message, onRetry }: { message: string; onRetry?: () => void }) {
  const { colors, spacing, fontSizes } = useTheme();
  return (
    <View style={styles.centered}>
      <Text style={{ color: colors.error, fontSize: fontSizes.sm }}>{message}</Text>
      {onRetry ? (
        <Pressable onPress={onRetry}>
          <Text style={{ color: colors.primary, marginTop: spacing.sm, fontWeight: '600' }}>
            Retry
          </Text>
        </Pressable>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  section: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase' },
  centered: { paddingVertical: 16, alignItems: 'center' },
});
