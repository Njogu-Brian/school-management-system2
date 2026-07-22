import { useCurrentUser, useStaffLeaveRequests } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const MyLeaveListScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const user = useCurrentUser();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const staffId = user?.staffId ?? 0;
  const leaveQuery = useStaffLeaveRequests(staffId, { enabled: staffId > 0 });

  const items = useMemo(() => leaveQuery.data?.data ?? [], [leaveQuery.data]);

  const statusColor = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'approved') return colors.success;
    if (s === 'rejected') return colors.error;
    if (s === 'pending') return colors.warning;
    return palette.textMuted;
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="My leave"
              subtitle="Your leave requests"
              onBack={() => navigation.goBack()}
            />
            <Button
              label="Apply for leave"
              onPress={() => navigation.navigate('LeaveApply')}
              style={{ marginBottom: spacing.sm }}
            />
          </View>
        }
        renderItem={({ item }) => {
          const status = item.status ?? 'pending';
          return (
            <Pressable
              style={[
                styles.row,
                {
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <Soft3DIcon name="calendar-outline" tone="amber" size={40} />
              <View style={{ flex: 1, marginLeft: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {item.leave_type_name ?? item.leave_type ?? 'Leave'}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {item.start_date} → {item.end_date}
                  {item.days != null || item.days_count != null
                    ? ` · ${item.days ?? item.days_count} day(s)`
                    : ''}
                </Text>
                {item.reason ? (
                  <Text
                    style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}
                    numberOfLines={2}
                  >
                    {item.reason}
                  </Text>
                ) : null}
              </View>
              <View style={[styles.badge, { backgroundColor: `${statusColor(status)}22` }]}>
                <Text
                  style={{
                    color: statusColor(status),
                    fontSize: 11,
                    fontWeight: '700',
                    textTransform: 'capitalize',
                  }}
                >
                  {status}
                </Text>
              </View>
            </Pressable>
          );
        }}
        refreshControl={
          <RefreshControl
            refreshing={leaveQuery.isRefetching}
            onRefresh={() => void leaveQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        ListEmptyComponent={
          !staffId ? (
            <EmptyState
              title="Staff profile missing"
              message="Your account is not linked to a staff record."
              icon="alert-circle-outline"
            />
          ) : leaveQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={4} />
          ) : leaveQuery.isError ? (
            <EmptyState
              title="Could not load leave"
              message={(leaveQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void leaveQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No leave requests"
              message="Apply for leave to see your history here."
              icon="calendar-outline"
              actionLabel="Apply"
              onAction={() => navigation.navigate('LeaveApply')}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8 },
});
