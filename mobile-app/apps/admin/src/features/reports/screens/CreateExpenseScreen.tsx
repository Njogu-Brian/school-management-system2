import { useCan, useCreateExpense, useUploadExpenseAttachment } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  DatePickerField,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import React, { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<ReportsStackParamList, 'CreateExpense'>;

function todayYmdFrom(d: Date): string {
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${m}-${day}`;
}

export const CreateExpenseScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { palette, spacing, radius, typography } = useTheme();
  const createMutation = useCreateExpense();
  const uploadMutation = useUploadExpenseAttachment();

  const [amount, setAmount] = useState('');
  const [date, setDate] = useState(() => new Date());
  const [notes, setNotes] = useState('');
  const [receipt, setReceipt] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [busy, setBusy] = useState(false);

  const pickCamera = async () => {
    try {
      const perm = await ImagePicker.requestCameraPermissionsAsync();
      if (!perm.granted) {
        showError('Camera', 'Camera permission is required to photograph a receipt.');
        return;
      }
      const picked = await ImagePicker.launchCameraAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        quality: 0.7,
      });
      if (picked.canceled || !picked.assets?.[0]) return;
      const asset = picked.assets[0];
      setReceipt({
        uri: asset.uri,
        name: asset.fileName ?? `receipt-${Date.now()}.jpg`,
        type: asset.mimeType ?? 'image/jpeg',
      });
    } catch (err) {
      showError('Camera', err instanceof Error ? err.message : 'Could not open camera.');
    }
  };

  const pickFile = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['image/*', 'application/pdf'],
        copyToCacheDirectory: true,
        multiple: false,
      });
      if (result.canceled || !result.assets?.length) return;
      const asset = result.assets[0];
      setReceipt({
        uri: asset.uri,
        name: asset.name ?? 'receipt',
        type: asset.mimeType ?? 'application/octet-stream',
      });
    } catch (err) {
      showError('File', err instanceof Error ? err.message : 'Could not pick a file.');
    }
  };

  const submit = async () => {
    const value = Number(amount.replace(/,/g, ''));
    if (!Number.isFinite(value) || value <= 0) {
      showError('Amount', 'Enter a valid amount.');
      return;
    }
    setBusy(true);
    try {
      const created = await createMutation.mutateAsync({
        expense_date: todayYmdFrom(date),
        amount: value,
        notes: notes.trim() || undefined,
        description: notes.trim() || 'Expense',
      });
      if (receipt) {
        await uploadMutation.mutateAsync({ expenseId: created.id, file: receipt });
      }
      showSuccess('Saved', 'Expense draft created.');
      navigation.replace('ExpenseDetail', { expenseId: created.id });
    } catch (err) {
      showError('Could not save', err instanceof Error ? err.message : 'Try again.');
    } finally {
      setBusy(false);
    }
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="New expense" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>You need permission to create expenses.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="New expense"
        subtitle="Amount, date, notes, and optional receipt"
        onBack={() => navigation.goBack()}
      />
      <TextField
        label="Amount (KES)"
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
        placeholder="0.00"
      />
      <View style={{ marginTop: spacing.md }}>
        <DatePickerField label="Date" value={date} onChange={setDate} />
      </View>
      <View style={{ marginTop: spacing.md }}>
        <TextField
          label="Notes"
          value={notes}
          onChangeText={setNotes}
          placeholder="What was this for?"
          multiline
        />
      </View>
      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.caption.fontSize,
          marginTop: spacing.md,
          marginBottom: spacing.xs,
        }}
      >
        Receipt (optional)
      </Text>
      <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
        <Pressable
          onPress={() => void pickCamera()}
          style={{
            flex: 1,
            borderWidth: 1,
            borderColor: palette.border,
            borderRadius: radius.md,
            padding: spacing.md,
            alignItems: 'center',
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>Camera</Text>
        </Pressable>
        <Pressable
          onPress={() => void pickFile()}
          style={{
            flex: 1,
            borderWidth: 1,
            borderColor: palette.border,
            borderRadius: radius.md,
            padding: spacing.md,
            alignItems: 'center',
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>File</Text>
        </Pressable>
      </View>
      {receipt ? (
        <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }} numberOfLines={1}>
          Attached: {receipt.name}
        </Text>
      ) : null}
      <Button label="Save expense" onPress={() => void submit()} loading={busy} />
    </ScreenContainer>
  );
};
