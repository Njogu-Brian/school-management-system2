import {
  useApprovalActions,
  useApprovalDetail,
  type ApprovalCompositeId,
  type ApprovalItem,
} from '@erp/core';
import {
  ApprovalActionBar,
  ApprovalDetailView,
  ScreenContainer,
  TextField,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation } from '@react-navigation/native';
import React, { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';
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
  const { palette, spacing, fontSizes, colors } = useTheme();
  const [rejectMode, setRejectMode] = useState(false);
  const [rejectReason, setRejectReason] = useState('');

  const detailQuery = useApprovalDetail(id as ApprovalCompositeId, initialItem);
  const { approve, reject } = useApprovalActions();

  const item: ApprovalItem | undefined = detailQuery.data ?? initialItem;
  const isSubmitting = approve.isPending || reject.isPending;

  const confirmApprove = () => {
    if (!item) return;
    Alert.alert('Approve', `Approve "${item.title}"?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Approve',
        onPress: () => {
          approve.mutate(
            { id: item.id },
            {
              onSuccess: () => navigation.goBack(),
              onError: (e) => Alert.alert('Error', (e as Error).message),
            },
          );
        },
      },
    ]);
  };

  const submitReject = () => {
    if (!item) return;
    const reason = rejectReason.trim();
    if (reason.length < 3) {
      Alert.alert('Reason required', 'Enter a rejection reason (min 3 characters).');
      return;
    }
    reject.mutate(
      { id: item.id, reason },
      {
        onSuccess: () => navigation.goBack(),
        onError: (e) => Alert.alert('Error', (e as Error).message),
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
        <Text style={{ color: palette.textSecondary }}>Loading…</Text>
      </ScreenContainer>
    );
  }

  if (!item) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error }}>Approval not found.</Text>
      </ScreenContainer>
    );
  }

  return (
    <View style={styles.flex}>
      <ScreenContainer style={styles.flex}>
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
              <Text style={{ color: colors.primary, fontWeight: '700', fontSize: fontSizes.sm }}>
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
              <Text
                onPress={submitReject}
                style={{
                  color: colors.error,
                  fontWeight: '700',
                  marginTop: spacing.sm,
                  fontSize: fontSizes.sm,
                }}
              >
                Confirm reject
              </Text>
            </View>
          ) : null}
        </ApprovalDetailView>
      </ScreenContainer>

      <ApprovalActionBar
        canAct={item.canAct}
        isSubmitting={isSubmitting}
        onApprove={confirmApprove}
        onReject={() => setRejectMode(true)}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
