import {
  useApprovalActions,
  useApprovalDetail,
  type ApprovalCompositeId,
  type ApprovalItem,
} from '@erp/core';
import {
  APPROVAL_ACTION_BAR_HEIGHT,
  ApprovalActionBar,
  ApprovalDetailView,
  Button,
  ConfirmDialog,
  ScreenContainer,
  TextField,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation } from '@react-navigation/native';
import React, { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';
import { showError } from '../../shared/utils/feedback';
import { buildApprovalDetailFields } from '../utils/detailFields';

type ApprovalDetailRoute = {
  ApprovalDetail: {
    id: ApprovalCompositeId;
    item: ApprovalItem;
  };
};

type Props = {
  route: RouteProp<ApprovalDetailRoute, 'ApprovalDetail'>;
  navigation: { goBack: () => void };
};

export const ApprovalDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { id, item: initialItem } = route.params;
  const rootNavigation = useNavigation();
  const { palette, spacing, typography, colors } = useTheme();
  /** ScreenContainer already applies bottom safe-area; only clear the tab chrome + action bar. */
  const tabClearance = useFloatingTabBarClearance(false);
  const [rejectMode, setRejectMode] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [rejectConfirmVisible, setRejectConfirmVisible] = useState(false);
  const [approveConfirmVisible, setApproveConfirmVisible] = useState(false);

  const detailQuery = useApprovalDetail(id as ApprovalCompositeId, initialItem);
  const { approve, reject } = useApprovalActions();

  const item: ApprovalItem | undefined = detailQuery.data ?? initialItem;
  const isSubmitting = approve.isPending || reject.isPending;

  const submitApprove = () => {
    if (!item) return;
    approve.mutate(
      { id: item.id },
      {
        onSuccess: () => {
          setApproveConfirmVisible(false);
          navigation.goBack();
        },
        onError: (e) => {
          setApproveConfirmVisible(false);
          showError('Error', (e as Error).message);
        },
      },
    );
  };

  const openRejectConfirm = () => {
    const reason = rejectReason.trim();
    if (reason.length < 3) {
      showError('Reason required', 'Enter a rejection reason (min 3 characters).');
      return;
    }
    setRejectConfirmVisible(true);
  };

  const submitReject = () => {
    if (!item) return;
    const reason = rejectReason.trim();
    reject.mutate(
      { id: item.id, reason },
      {
        onSuccess: () => {
          setRejectConfirmVisible(false);
          navigation.goBack();
        },
        onError: (e) => {
          setRejectConfirmVisible(false);
          showError('Error', (e as Error).message);
        },
      },
    );
  };

  const openApplication = () => {
    if (!item || item.sourceType !== 'online_admission') return;
    navigateToDrawer(rootNavigation, 'Admissions', 'ApplicationDetail', {
      applicationId: item.sourceId,
    });
  };

  if (detailQuery.isLoading && !item) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
          Loading…
        </Text>
      </ScreenContainer>
    );
  }

  if (!item) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error, fontSize: typography.body.fontSize }}>
          Approval not found.
        </Text>
      </ScreenContainer>
    );
  }

  const scrollBottomPad =
    tabClearance + (item.canAct ? APPROVAL_ACTION_BAR_HEIGHT + spacing.md : 0);

  return (
    <View style={styles.flex}>
      <ScreenContainer
        style={styles.flex}
        contentContainerStyle={{ paddingBottom: scrollBottomPad }}
      >
        <ApprovalDetailView
          title={item.title}
          subtitle={item.subtitle}
          status={item.status}
          priority={item.priority}
          fields={buildApprovalDetailFields(item)}
          summary={item.summary}
        >
          {item.sourceType === 'online_admission' ? (
            <Pressable onPress={openApplication} style={{ marginTop: spacing.md }}>
              <Text
                style={{
                  color: colors.primary,
                  fontWeight: '700',
                  fontSize: typography.label.fontSize,
                }}
              >
                Open application in Admissions →
              </Text>
            </Pressable>
          ) : null}
          {rejectMode ? (
            <View style={{ marginTop: spacing.lg, paddingHorizontal: spacing.md }}>
              <TextField
                label="Rejection reason"
                value={rejectReason}
                onChangeText={setRejectReason}
                placeholder="Required for rejection"
                multiline
              />
              <View style={{ marginTop: spacing.sm }}>
                <Button
                  label="Confirm reject"
                  variant="destructive"
                  onPress={openRejectConfirm}
                  disabled={isSubmitting}
                />
              </View>
            </View>
          ) : null}
        </ApprovalDetailView>
      </ScreenContainer>

      <ApprovalActionBar
        canAct={item.canAct}
        isSubmitting={isSubmitting}
        showApprove={item.sourceType !== 'online_admission'}
        onApprove={() => setApproveConfirmVisible(true)}
        onReject={() => setRejectMode(true)}
      />

      <ConfirmDialog
        visible={approveConfirmVisible}
        title="Approve"
        message={`Approve "${item.title}"?`}
        confirmLabel="Approve"
        cancelLabel="Cancel"
        loading={approve.isPending}
        onConfirm={submitApprove}
        onCancel={() => setApproveConfirmVisible(false)}
      />

      <ConfirmDialog
        visible={rejectConfirmVisible}
        title="Reject approval"
        message={`Reject "${item.title}"? This cannot be undone.`}
        confirmLabel="Reject"
        cancelLabel="Cancel"
        destructive
        loading={reject.isPending}
        onConfirm={submitReject}
        onCancel={() => setRejectConfirmVisible(false)}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
