import { studentsApi, useStudentDetail, useStudentFinanceSearch, type InvoiceDetailRecord } from '@erp/core';
import { Button, TextField, useTheme } from '@erp/ui';
import React, { useEffect, useMemo, useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

export interface MpesaPromptSheetProps {
  visible: boolean;
  onClose: () => void;
  studentId: number;
  studentName: string;
  invoice: InvoiceDetailRecord;
}

export const MpesaPromptSheet: React.FC<MpesaPromptSheetProps> = ({
  visible,
  onClose,
  studentId,
  studentName,
  invoice,
}) => {
  const { palette, spacing, typography, radius } = useTheme();
  const [phone, setPhone] = useState('');
  const [shareSiblings, setShareSiblings] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const detailQuery = useStudentDetail(studentId, { enabled: visible });
  const searchQuery = useStudentFinanceSearch(studentName, {
    enabled: visible && studentName.length >= 2,
  });
  const studentMatch = useMemo(
    () => searchQuery.data?.find((s) => s.id === studentId),
    [searchQuery.data, studentId],
  );
  const siblings = studentMatch?.siblings ?? [];

  useEffect(() => {
    if (!visible) return;
    setPhone('');
    setShareSiblings(false);
  }, [visible, invoice.id]);

  const amount = invoice.balance > 0 ? invoice.balance : 0;

  const submit = async (phoneNumber: string) => {
    if (!phoneNumber.trim()) {
      showError('Phone required', 'Enter a parent phone number or pick a saved contact.');
      return;
    }
    if (amount <= 0) {
      showError('Nothing to pay', 'This invoice has no outstanding balance.');
      return;
    }

    setSubmitting(true);
    try {
      const payload: Parameters<typeof studentsApi.promptMpesa>[1] = {
        phone_number: phoneNumber.trim(),
        amount,
        invoice_id: shareSiblings ? null : invoice.id,
        share_with_siblings: shareSiblings && siblings.length > 0,
      };

      if (shareSiblings && siblings.length > 0) {
        const perSibling = Math.round((amount / (siblings.length + 1)) * 100) / 100;
        payload.sibling_allocations = [
          { student_id: studentId, amount: perSibling },
          ...siblings.map((s) => ({ student_id: s.id, amount: perSibling })),
        ];
      }

      const res = await studentsApi.promptMpesa(studentId, payload);
      if (!res.success) {
        throw new Error(res.message || 'Failed to send M-PESA prompt.');
      }
      showSuccess(
        'STK sent',
        res.message ?? 'Check the parent phone for the M-PESA payment prompt.',
      );
      onClose();
    } catch (err) {
      showError('Prompt failed', err instanceof Error ? err.message : 'Could not send STK push.');
    } finally {
      setSubmitting(false);
    }
  };

  const contactOptions = useMemo(() => {
    const opts: Array<{ label: string; phone: string }> = [];
    const parent = detailQuery.data?.parent;
    if (parent?.fatherPhone) {
      opts.push({ label: parent.fatherName ?? 'Father', phone: parent.fatherPhone });
    }
    if (parent?.motherPhone) {
      opts.push({ label: parent.motherName ?? 'Mother', phone: parent.motherPhone });
    }
    if (parent?.guardianPhone) {
      opts.push({ label: parent.guardianName ?? 'Guardian', phone: parent.guardianPhone });
    }
    return opts;
  }, [detailQuery.data?.parent]);

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <Pressable style={styles.backdrop} onPress={onClose}>
        <Pressable
          style={[
            styles.sheet,
            {
              backgroundColor: palette.surface,
              padding: spacing.mdLg,
              borderTopLeftRadius: radius.sheet,
              borderTopRightRadius: radius.sheet,
            },
          ]}
          onPress={() => undefined}
        >
          <ScrollView keyboardShouldPersistTaps="handled">
            <Text style={{ fontWeight: '700', fontSize: typography.title.fontSize, color: palette.textMain }}>
              Prompt parent to pay
            </Text>
            <Text style={{ color: palette.textSub, marginTop: spacing.xs, marginBottom: spacing.md }}>
              {invoice.invoice_number} · Balance {amount.toLocaleString('en-KE')} KES
            </Text>

            <TextField
              label="M-PESA phone"
              value={phone}
              onChangeText={setPhone}
              keyboardType="phone-pad"
              placeholder="0712345678"
            />

            {siblings.length > 0 ? (
              <View style={{ flexDirection: 'row', alignItems: 'center', marginVertical: spacing.sm }}>
                <Switch value={shareSiblings} onValueChange={setShareSiblings} />
                <Text style={{ marginLeft: spacing.sm, color: palette.textMain, flex: 1 }}>
                  Share payment with {siblings.length} sibling{siblings.length === 1 ? '' : 's'}
                </Text>
              </View>
            ) : null}

            <Text
              style={{
                color: palette.textSub,
                fontSize: typography.caption.fontSize,
                marginBottom: spacing.xs,
              }}
            >
              Saved contacts
            </Text>
            {contactOptions.map((c) => (
              <Pressable
                key={`${c.label}-${c.phone}`}
                onPress={() => void submit(c.phone)}
                style={{ paddingVertical: spacing.sm, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: palette.border }}
              >
                <Text style={{ color: palette.textMain, fontWeight: '600' }}>{c.label}</Text>
                <Text style={{ color: palette.textSub, fontSize: typography.body.fontSize }}>{c.phone}</Text>
              </Pressable>
            ))}

            <Button
              label="Send STK push"
              onPress={() => void submit(phone)}
              loading={submitting}
              style={{ marginTop: spacing.sm }}
            />
            <Button label="Cancel" variant="secondary" onPress={onClose} style={{ marginTop: spacing.xs }} />
          </ScrollView>
        </Pressable>
      </Pressable>
    </Modal>
  );
};

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'flex-end' },
  sheet: { maxHeight: '80%' },
});
