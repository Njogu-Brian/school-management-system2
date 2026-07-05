import {
  enqueueSyncItem,
  useNetworkStatus,
  useReconciliationActions,
  useStudentFinanceSearch,
  type FinanceTransactionDetailRecord,
  type StudentFinanceSearchResult,
} from '@erp/core';
import { Button, FinanceSearchBar, useTheme } from '@erp/ui';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';

type ShareRow = { studentId: number; name: string; amount: string };

interface Props {
  transactionId: number;
  transactionType: 'bank' | 'c2b';
  txn: FinanceTransactionDetailRecord;
  canAct: boolean;
  onUpdated: () => void;
}

export const TransactionAssignSection: React.FC<Props> = ({
  transactionId,
  transactionType,
  txn,
  canAct,
  onUpdated,
}) => {
  const { colors, palette, spacing, fontSizes, radius } = useTheme();
  const networkStatus = useNetworkStatus();
  const { assign, share } = useReconciliationActions();
  const [searchInput, setSearchInput] = useState('');
  const [debounced, setDebounced] = useState('');
  const [shareOpen, setShareOpen] = useState(false);
  const [shareRows, setShareRows] = useState<ShareRow[]>([]);
  const [selectedStudent, setSelectedStudent] = useState<StudentFinanceSearchResult | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  const searchQuery = useStudentFinanceSearch(debounced, {
    enabled: canAct && debounced.length >= 2,
  });

  const txAmount = txn.trans_amount ?? txn.amount ?? 0;

  const openShare = (student: StudentFinanceSearchResult) => {
    const siblings = student.siblings ?? [];
    const rows: ShareRow[] = [
      { studentId: student.id, name: student.full_name, amount: String(txAmount) },
      ...siblings.map((s) => ({
        studentId: s.id,
        name: s.full_name,
        amount: '',
      })),
    ];
    setShareRows(rows.length ? rows : [{ studentId: student.id, name: student.full_name, amount: String(txAmount) }]);
    setSelectedStudent(student);
    setShareOpen(true);
  };

  const runAssign = async (student: StudentFinanceSearchResult) => {
    if ((student.siblings?.length ?? 0) > 0) {
      openShare(student);
      return;
    }

    if (networkStatus === 'offline') {
      await enqueueSyncItem('finance.assign', {
        transactionId,
        type: transactionType,
        studentId: student.id,
      });
      Alert.alert('Queued offline', 'Assignment will sync when you reconnect.');
      return;
    }

    try {
      await assign.mutateAsync({
        id: transactionId,
        type: transactionType,
        studentId: student.id,
      });
      Alert.alert('Assigned', `${student.full_name} assigned to this transaction.`);
      onUpdated();
    } catch (err) {
      Alert.alert('Assign failed', (err as Error).message);
    }
  };

  const submitShare = async () => {
    const allocations = shareRows
      .map((r) => ({
        student_id: r.studentId,
        amount: parseFloat(String(r.amount).replace(/,/g, '')),
      }))
      .filter((a) => a.student_id > 0 && !Number.isNaN(a.amount) && a.amount > 0);

    if (!allocations.length) {
      Alert.alert('Share', 'Enter at least one amount greater than 0.');
      return;
    }

    const total = allocations.reduce((s, a) => s + a.amount, 0);
    if (total - txAmount > 0.01) {
      Alert.alert('Share', 'Total allocation cannot exceed the transaction amount.');
      return;
    }

    if (networkStatus === 'offline') {
      await enqueueSyncItem('finance.share', {
        transactionId,
        type: transactionType,
        allocations,
      });
      Alert.alert('Queued offline', 'Share will sync when you reconnect.');
      setShareOpen(false);
      return;
    }

    try {
      await share.mutateAsync({
        id: transactionId,
        type: transactionType,
        allocations,
      });
      Alert.alert('Shared', 'Transaction split among siblings.');
      setShareOpen(false);
      onUpdated();
    } catch (err) {
      Alert.alert('Share failed', (err as Error).message);
    }
  };

  const canConfirm = useMemo(
    () => canAct && (Boolean(txn.student_id) || Boolean(txn.is_shared)),
    [canAct, txn.is_shared, txn.student_id],
  );

  if (!canAct && !txn.student_id) {
    return null;
  }

  return (
    <>
      {canAct && !txn.student_id ? (
        <View style={{ marginTop: spacing.md }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
            Assign student
          </Text>
          <FinanceSearchBar
            value={searchInput}
            onChangeText={setSearchInput}
            placeholder="Search name or admission #…"
          />
          {searchQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.sm }} />
          ) : null}
          {(searchQuery.data ?? []).map((student) => (
            <Pressable
              key={student.id}
              onPress={() => void runAssign(student)}
              style={{
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.md,
                padding: spacing.sm,
                marginTop: spacing.xs,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{student.label}</Text>
              {(student.siblings?.length ?? 0) > 0 ? (
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                  {student.siblings.length} sibling(s) — tap to split amount
                </Text>
              ) : null}
            </Pressable>
          ))}
        </View>
      ) : null}

      {txn.student_id && canAct ? (
        <View style={{ marginTop: spacing.sm }}>
          <Button
            label="Share among siblings"
            variant="secondary"
            onPress={() => {
              if (selectedStudent) {
                openShare(selectedStudent);
              } else {
                setShareRows([
                  {
                    studentId: txn.student_id!,
                    name: txn.student_name ?? 'Student',
                    amount: String(txAmount),
                  },
                ]);
                setShareOpen(true);
              }
            }}
            loading={share.isPending}
            disabled={assign.isPending}
          />
        </View>
      ) : null}

      {!canConfirm && canAct ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.sm }}>
          Assign a student (or share among siblings) before confirming.
        </Text>
      ) : null}

      <Modal visible={shareOpen} animationType="slide" transparent onRequestClose={() => setShareOpen(false)}>
        <View style={{ flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.45)' }}>
          <View
            style={{
              backgroundColor: palette.surface,
              borderTopLeftRadius: radius.lg,
              borderTopRightRadius: radius.lg,
              padding: spacing.md,
              maxHeight: '80%',
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.md }}>
              Share among siblings
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginVertical: spacing.sm }}>
              Total: KES {txAmount.toLocaleString('en-KE')}
            </Text>
            <ScrollView style={{ maxHeight: 320 }}>
              {shareRows.map((row, index) => (
                <View key={row.studentId} style={{ marginBottom: spacing.sm }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
                    {row.name}
                  </Text>
                  <TextInput
                    value={row.amount}
                    onChangeText={(text) => {
                      const next = [...shareRows];
                      next[index] = { ...row, amount: text };
                      setShareRows(next);
                    }}
                    keyboardType="decimal-pad"
                    placeholder="Amount"
                    placeholderTextColor={palette.textSecondary}
                    style={{
                      borderWidth: 1,
                      borderColor: palette.border,
                      borderRadius: radius.sm,
                      padding: spacing.sm,
                      marginTop: 4,
                      color: palette.textPrimary,
                    }}
                  />
                </View>
              ))}
            </ScrollView>
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm }}>
              <Button label="Cancel" variant="ghost" onPress={() => setShareOpen(false)} />
              <Button label="Apply share" onPress={() => void submitShare()} loading={share.isPending} />
            </View>
          </View>
        </View>
      </Modal>
    </>
  );
};
