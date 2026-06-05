import { useCan, useCommunicationTemplates, useSendSms } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsCompose'>;

const SMS_SEGMENT = 160;

export const SmsComposeScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const templatesQuery = useCommunicationTemplates({ enabled: canView });
  const sendMutation = useSendSms();
  const [message, setMessage] = useState('');
  const [phones, setPhones] = useState('');
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | undefined>();

  const charCount = message.length;
  const segments = Math.max(1, Math.ceil(charCount / SMS_SEGMENT));
  const estimatedCost = useMemo(() => segments * (phones.split(/[,;\s]+/).filter(Boolean).length || 1), [segments, phones]);

  const onSend = async () => {
    if (!message.trim() && !selectedTemplateId) {
      showError('Missing fields', 'Enter a message or select a template.');
      return;
    }
    if (!phones.trim()) {
      showError('Missing fields', 'Enter at least one phone number.');
      return;
    }
    try {
      const res = await sendMutation.mutateAsync({
        message: message.trim() || undefined,
        template_id: selectedTemplateId,
        custom_numbers: phones.trim(),
      });
      showSuccess('SMS sent', res.message ?? `Sent: ${res.data?.sent ?? 0}, failed: ${res.data?.failed ?? 0}`, () =>
        navigation.goBack(),
      );
    } catch (err) {
      showError('Send failed', (err as Error).message);
    }
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Send SMS" onBack={() => navigation.goBack()} />

      {templatesQuery.data && templatesQuery.data.length > 0 ? (
        <View style={{ marginBottom: spacing.md }}>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
            Template selector
          </Text>
          {templatesQuery.data.map((tpl) => (
            <Pressable
              key={tpl.id}
              onPress={() => {
                setSelectedTemplateId(tpl.id);
                setMessage(tpl.content ?? '');
              }}
              style={[
                styles.chip,
                { borderColor: palette.border, marginBottom: spacing.xs },
                selectedTemplateId === tpl.id && { borderColor: colors.primary, backgroundColor: '#E8F0FA' },
              ]}
            >
              <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm }}>{tpl.title}</Text>
            </Pressable>
          ))}
        </View>
      ) : null}

      <Text style={styles.label}>Phone numbers (comma-separated)</Text>
      <TextInput
        value={phones}
        onChangeText={setPhones}
        placeholder="2547XXXXXXXX, 2541XXXXXXXX"
        placeholderTextColor={palette.textSecondary}
        style={[styles.input, { borderColor: palette.border, color: palette.textPrimary }]}
      />

      <Text style={styles.label}>Message</Text>
      <TextInput
        value={message}
        onChangeText={setMessage}
        multiline
        placeholder="SMS body"
        placeholderTextColor={palette.textSecondary}
        style={[styles.input, styles.textArea, { borderColor: palette.border, color: palette.textPrimary }]}
      />

      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.xs }}>
        {charCount} chars · {segments} segment(s) · est. {estimatedCost} credit(s)
      </Text>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
        Class/parent recipient selection is available on the web portal.
      </Text>

      <Pressable
        onPress={() => void onSend()}
        disabled={sendMutation.isPending}
        style={[styles.sendBtn, { backgroundColor: colors.primary, opacity: sendMutation.isPending ? 0.6 : 1 }]}
      >
        <Text style={{ color: '#fff', fontWeight: '700' }}>
          {sendMutation.isPending ? 'Sending…' : 'Send now'}
        </Text>
      </Pressable>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  label: { fontWeight: '600', marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderRadius: 8, padding: 12 },
  textArea: { minHeight: 120, textAlignVertical: 'top' },
  sendBtn: { marginTop: 20, padding: 14, borderRadius: 8, alignItems: 'center' },
  chip: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 10 },
});
