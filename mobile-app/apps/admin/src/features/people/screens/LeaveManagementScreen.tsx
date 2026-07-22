import { useApprovalActions, useApprovalList, type ApprovalItem } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ConfirmDialog,
  ListEmptyState,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'LeaveManagement'>;

export const LeaveManagementScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const listQuery = useApprovalList({
    filters: { status: 'pending', sourceType: 'leave_request', priority: 'all' },
    includeAdmissions: false,
  });
  const actions = useApprovalActions();
  const [approveTarget, setApproveTarget] = useState<ApprovalItem | null>(null);
  const [rejectTarget, setRejectTarget] = useState<ApprovalItem | null>(null);
  const [rejectReason, setRejectReason] = useState('');

  const items = (listQuery.data ?? []).filter((i) => i.sourceType === 'leave_request');

  const submitApprove = () => {
    if (!approveTarget) return;
    const item = approveTarget;
    setApproveTarget(null);
    void actions.approve
      .mutateAsync({ id: item.id })
      .then(() => showSuccess('Approved', 'Leave request approved.'))
      .catch((e) => showError('Error', (e as Error).message));
  };

  const submitReject = () => {
    if (!rejectTarget) return;
    const reason = rejectReason.trim();
    if (reason.length < 3) {
      showError('Reason required', 'Enter a rejection reason (min 3 characters).');
      return;
    }
    const item = rejectTarget;
    setRejectTarget(null);
    setRejectReason('');
    void actions.reject
      .mutateAsync({ id: item.id, reason })
      .then(() => showSuccess('Rejected', 'Leave request rejected.'))
      .catch((e) => showError('Error', (e as Error).message));
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Leave approvals"
          subtitle="Approve or reject pending leave requests"
          onBack={() => navigation.goBack()}
        />
        {listQuery.isLoading ? <ActivityIndicator color={colors.primary} /> : null}
        <FlatList
          data={items}
          keyExtractor={(item) => item.id}
          contentContainerStyle={{ paddingBottom: spacing.xl }}
          renderItem={({ item }) => {
            const rejecting = rejectTarget?.id === item.id;
            return (
              <View
                style={[
                  styles.row,
                  {
                    borderColor: palette.borderSubtle,
                    backgroundColor: palette.surfaceRaised,
                    borderRadius: radius.card,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                  },
                ]}
              >
                <Text
                  style={{
                    color: palette.textPrimary,
                    fontWeight: '600',
                    fontSize: typography.body.fontSize,
                  }}
                >
                  {item.title}
                </Text>
                <Text
                  style={{
                    color: palette.textSecondary,
                    fontSize: typography.caption.fontSize,
                    marginTop: 2,
                  }}
                >
                  {item.subtitle}
                </Text>
                {rejecting ? (
                  <View style={{ marginTop: spacing.sm }}>
                    <TextField
                      label="Rejection reason"
                      value={rejectReason}
                      onChangeText={setRejectReason}
                      placeholder="Required for rejection"
                      multiline
                    />
                    <View style={[styles.actions, { gap: spacing.sm, marginTop: spacing.sm }]}>
                      <View style={{ flex: 1 }}>
                        <Button
                          label="Confirm reject"
                          variant="destructive"
                          onPress={submitReject}
                          disabled={actions.reject.isPending}
                        />
                      </View>
                      <Pressable
                        onPress={() => {
                          setRejectTarget(null);
                          setRejectReason('');
                        }}
                        style={[styles.btn, { borderRadius: radius.sm, flex: 1 }]}
                      >
                        <Text
                          style={{
                            color: palette.textSecondary,
                            fontWeight: '600',
                            textAlign: 'center',
                          }}
                        >
                          Cancel
                        </Text>
                      </Pressable>
                    </View>
                  </View>
                ) : (
                  <View style={[styles.actions, { gap: spacing.sm, marginTop: spacing.sm }]}>
                    <Pressable
                      onPress={() => setApproveTarget(item)}
                      style={[
                        styles.btn,
                        { backgroundColor: colors.success, borderRadius: radius.sm, flex: 1 },
                      ]}
                    >
                      <Text style={{ color: '#fff', fontWeight: '700', textAlign: 'center' }}>
                        Approve
                      </Text>
                    </Pressable>
                    <Pressable
                      onPress={() => {
                        setRejectReason('');
                        setRejectTarget(item);
                      }}
                      style={[
                        styles.btn,
                        { backgroundColor: colors.error, borderRadius: radius.sm, flex: 1 },
                      ]}
                    >
                      <Text style={{ color: '#fff', fontWeight: '700', textAlign: 'center' }}>
                        Reject
                      </Text>
                    </Pressable>
                  </View>
                )}
              </View>
            );
          }}
          ListEmptyComponent={
            !listQuery.isLoading ? (
              listQuery.isError ? (
                <ListEmptyState
                  title="Could not load leave requests"
                  message={(listQuery.error as Error)?.message ?? 'Failed to load leave requests.'}
                  icon="alert-circle-outline"
                  actionLabel="Retry"
                  onAction={() => void listQuery.refetch()}
                />
              ) : (
                <ListEmptyState entityName="pending leave requests" icon="calendar-outline" />
              )
            ) : null
          }
        />
      </View>

      <ConfirmDialog
        visible={approveTarget != null}
        title="Approve leave"
        message="Approve this leave request?"
        confirmLabel="Approve"
        cancelLabel="Cancel"
        loading={actions.approve.isPending}
        onConfirm={submitApprove}
        onCancel={() => setApproveTarget(null)}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  actions: { flexDirection: 'row', alignItems: 'center' },
  btn: { paddingVertical: 10, paddingHorizontal: 12 },
});
