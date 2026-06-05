import { useCommunicationTemplates, useSendSms } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsCompose'>;

export const SmsComposeScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const templatesQuery = useCommunicationTemplates();
  const sendMutation = useSendSms();
  const [message, setMessage] = useState('');
  const [phones, setPhones] = useState('');

  const onSend = async () => {
    if (!message.trim() && !phones.trim()) {
      Alert.alert('Missing fields', 'Enter a message and at least one phone number.');
      return;
    }
    try {
      const res = await sendMutation.mutateAsync({
        message: message.trim(),
        custom_numbers: phones.trim(),
      });
      Alert.alert('SMS sent', res.message ?? 'Dispatch complete.');
      navigation.goBack();
    } catch (err) {
      Alert.alert('Send failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Send SMS" subtitle="POST /communication/sms" onBack={() => navigation.goBack()} />

      {templatesQuery.data && templatesQuery.data.length > 0 ? (
        <View style={{ marginBottom: spacing.md }}>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
            Tap a template to use its body
          </Text>
          {templatesQuery.data.slice(0, 5).map((tpl) => (
            <Pressable
              key={tpl.id}
              onPress={() => setMessage(tpl.content ?? '')}
              style={[styles.chip, { borderColor: palette.border, marginBottom: spacing.xs }]}
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
        style={[
          styles.input,
          styles.textArea,
          { borderColor: palette.border, color: palette.textPrimary },
        ]}
      />

      <Pressable
        onPress={() => void onSend()}
        disabled={sendMutation.isPending}
        style={[styles.sendBtn, { backgroundColor: colors.primary, opacity: sendMutation.isPending ? 0.6 : 1 }]}
      >
        <Text style={{ color: '#fff', fontWeight: '700' }}>
          {sendMutation.isPending ? 'Sending…' : 'Send SMS'}
        </Text>
      </Pressable>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  label: { fontWeight: '600', marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderRadius: 8, padding: 12 },
  textArea: { minHeight: 120, textAlignVertical: 'top' },
  sendBtn: { marginTop: 20, padding: 14, borderRadius: 8, alignItems: 'center' },
  chip: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 10 },
});
