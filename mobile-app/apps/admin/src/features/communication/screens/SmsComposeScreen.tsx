import { useCan, useCommunicationTemplates, useSendSms } from '@erp/core';
import {
  AcademicScreenHeader,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsCompose'>;

const SMS_SEGMENT = 160;
type SenderId = 'default' | 'finance';

export const SmsComposeScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const templatesQuery = useCommunicationTemplates({ enabled: canView });
  const sendMutation = useSendSms();
  const [message, setMessage] = useState('');
  const [phones, setPhones] = useState('');
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | undefined>();
  const [senderId, setSenderId] = useState<SenderId>('default');

  const recipientCount = useMemo(
    () => phones.split(/[,;\s]+/).filter(Boolean).length,
    [phones],
  );
  const charCount = message.length;
  const segments = Math.max(1, Math.ceil(charCount / SMS_SEGMENT));
  const estimatedCost = segments * (recipientCount || 1);

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
        sender_id: senderId,
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

  const inputStyle = [
    styles.input,
    {
      borderColor: palette.borderSubtle,
      backgroundColor: palette.surfaceRaised,
      color: palette.textPrimary,
      borderRadius: radius.control,
      fontSize: typography.body.fontSize,
    },
  ];
  const labelStyle = {
    color: palette.textSecondary,
    fontSize: typography.caption.fontSize,
    fontWeight: '600' as const,
    marginBottom: 6,
    marginTop: spacing.md,
    textTransform: 'uppercase' as const,
    letterSpacing: 0.4,
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Send SMS" subtitle="Broadcast to custom numbers" onBack={() => navigation.goBack()} />

      {templatesQuery.data && templatesQuery.data.length > 0 ? (
        <View style={{ marginBottom: spacing.sm }}>
          <Text style={labelStyle}>Template</Text>
          <FilterChipRow>
            <FilterChip
              label="None"
              active={selectedTemplateId == null}
              onPress={() => {
                setSelectedTemplateId(undefined);
              }}
            />
            {templatesQuery.data.map((tpl) => (
              <FilterChip
                key={tpl.id}
                label={tpl.title}
                active={selectedTemplateId === tpl.id}
                onPress={() => {
                  setSelectedTemplateId(tpl.id);
                  setMessage(tpl.content ?? '');
                }}
              />
            ))}
          </FilterChipRow>
        </View>
      ) : null}

      <Text style={labelStyle}>Sender ID</Text>
      <FilterChipRow>
        <FilterChip label="School (default)" active={senderId === 'default'} onPress={() => setSenderId('default')} />
        <FilterChip label="Finance" active={senderId === 'finance'} onPress={() => setSenderId('finance')} />
      </FilterChipRow>

      <Text style={labelStyle}>Phone numbers (comma-separated)</Text>
      <TextInput
        value={phones}
        onChangeText={setPhones}
        placeholder="2547XXXXXXXX, 2541XXXXXXXX"
        placeholderTextColor={palette.textMuted}
        keyboardType="phone-pad"
        style={inputStyle}
      />
      {recipientCount > 0 ? (
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
          {recipientCount} recipient{recipientCount === 1 ? '' : 's'}
        </Text>
      ) : null}

      <Text style={labelStyle}>Message</Text>
      <TextInput
        value={message}
        onChangeText={setMessage}
        multiline
        placeholder="SMS body"
        placeholderTextColor={palette.textMuted}
        style={[...inputStyle, styles.textArea]}
      />

      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
        {charCount} chars · {segments} segment{segments === 1 ? '' : 's'} · est. {estimatedCost} credit
        {estimatedCost === 1 ? '' : 's'}
      </Text>
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
        Class/parent recipient selection is available on the web portal.
      </Text>

      <Pressable
        onPress={() => void onSend()}
        disabled={sendMutation.isPending}
        accessibilityRole="button"
        style={({ pressed }) => [
          styles.sendBtn,
          {
            backgroundColor: colors.primary,
            borderRadius: radius.md,
            opacity: sendMutation.isPending ? 0.6 : pressed ? 0.85 : 1,
          },
        ]}
      >
        <Text style={{ color: colors.white, fontWeight: '700', fontSize: typography.body.fontSize }}>
          {sendMutation.isPending ? 'Sending…' : 'Send now'}
        </Text>
      </Pressable>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  input: { borderWidth: StyleSheet.hairlineWidth, padding: 14 },
  textArea: { minHeight: 120, textAlignVertical: 'top' },
  sendBtn: { marginTop: 24, padding: 16, alignItems: 'center' },
});
