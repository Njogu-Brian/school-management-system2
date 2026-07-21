import {
  useCan,
  useCommunicationTemplates,
  useSendSms,
  useSendWhatsApp,
  useSettingsClasses,
  useSmsRecipients,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterBottomSheet,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsCompose'>;

const SMS_SEGMENT = 160;
type SenderId = 'default' | 'finance';
type Channel = 'sms' | 'whatsapp';

export const SmsComposeScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [channel, setChannel] = useState<Channel>('sms');
  const templatesQuery = useCommunicationTemplates({ enabled: canView, type: channel });
  const sendMutation = useSendSms();
  const sendWhatsAppMutation = useSendWhatsApp();
  const [message, setMessage] = useState('');
  const [phones, setPhones] = useState('');
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | undefined>();
  const [senderId, setSenderId] = useState<SenderId>('default');
  const [pickerVisible, setPickerVisible] = useState(false);
  const [pickerClassId, setPickerClassId] = useState<number | undefined>();

  const classesQuery = useSettingsClasses({ enabled: canView });
  const recipientsQuery = useSmsRecipients({
    enabled: canView && pickerVisible,
    classroomId: pickerClassId,
  });

  const applyRecipients = () => {
    const fetched = recipientsQuery.data?.recipients ?? [];
    if (fetched.length === 0) {
      setPickerVisible(false);
      return;
    }
    const existing = phones.split(/[,;\s]+/).filter(Boolean);
    const merged = Array.from(new Set([...existing, ...fetched.map((r) => r.phone)]));
    setPhones(merged.join(', '));
    setPickerVisible(false);
    showSuccess('Recipients added', `${fetched.length} parent contact${fetched.length === 1 ? '' : 's'} added.`);
  };

  const recipientCount = useMemo(
    () => phones.split(/[,;\s]+/).filter(Boolean).length,
    [phones],
  );
  const charCount = message.length;
  const segments = Math.max(1, Math.ceil(charCount / SMS_SEGMENT));
  const estimatedCost = segments * (recipientCount || 1);

  const pending = sendMutation.isPending || sendWhatsAppMutation.isPending;

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
      const res =
        channel === 'whatsapp'
          ? await sendWhatsAppMutation.mutateAsync({
              message: message.trim() || undefined,
              template_id: selectedTemplateId,
              custom_numbers: phones.trim(),
            })
          : await sendMutation.mutateAsync({
              message: message.trim() || undefined,
              template_id: selectedTemplateId,
              custom_numbers: phones.trim(),
              sender_id: senderId,
            });
      showSuccess(
        channel === 'whatsapp' ? 'WhatsApp sent' : 'SMS sent',
        res.message ?? `Sent: ${res.data?.sent ?? 0}, failed: ${res.data?.failed ?? 0}`,
        () => navigation.goBack(),
      );
    } catch (err) {
      showError('Send failed', (err as Error).message);
    }
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need communication.view permission to send messages."
          icon="lock-closed-outline"
        />
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
      padding: spacing.mdSm,
    },
  ];
  const labelStyle = {
    color: palette.textSecondary,
    fontSize: typography.caption.fontSize,
    fontWeight: '600' as const,
    marginBottom: spacing.xs,
    marginTop: spacing.md,
    textTransform: 'uppercase' as const,
    letterSpacing: 0.4,
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Send message"
        subtitle="Broadcast via SMS or WhatsApp"
        onBack={() => navigation.goBack()}
      />

      <Text style={[labelStyle, { marginTop: 0 }]}>Channel</Text>
      <FilterChipRow>
        <FilterChip
          label="SMS"
          active={channel === 'sms'}
          onPress={() => {
            setChannel('sms');
            setSelectedTemplateId(undefined);
          }}
        />
        <FilterChip
          label="WhatsApp"
          active={channel === 'whatsapp'}
          onPress={() => {
            setChannel('whatsapp');
            setSelectedTemplateId(undefined);
          }}
        />
      </FilterChipRow>

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

      {channel === 'sms' ? (
        <>
          <Text style={labelStyle}>Sender ID</Text>
          <FilterChipRow>
            <FilterChip label="School (default)" active={senderId === 'default'} onPress={() => setSenderId('default')} />
            <FilterChip label="Finance" active={senderId === 'finance'} onPress={() => setSenderId('finance')} />
          </FilterChipRow>
        </>
      ) : null}

      <Text style={labelStyle}>Phone numbers (comma-separated)</Text>
      <TextInput
        value={phones}
        onChangeText={setPhones}
        placeholder="2547XXXXXXXX, 2541XXXXXXXX"
        placeholderTextColor={palette.textMuted}
        keyboardType="phone-pad"
        style={inputStyle}
      />
      <View style={[styles.recipientRow, { marginTop: spacing.xs }]}>
        {recipientCount > 0 ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            {recipientCount} recipient{recipientCount === 1 ? '' : 's'}
          </Text>
        ) : (
          <View />
        )}
        <Pressable onPress={() => setPickerVisible(true)} accessibilityRole="button" hitSlop={8}>
          <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
            + Add parents by class
          </Text>
        </Pressable>
      </View>

      <Text style={labelStyle}>Message</Text>
      <TextInput
        value={message}
        onChangeText={setMessage}
        multiline
        placeholder="SMS body"
        placeholderTextColor={palette.textMuted}
        style={[...inputStyle, styles.textArea]}
      />

      {channel === 'sms' ? (
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
          {charCount} chars · {segments} segment{segments === 1 ? '' : 's'} · est. {estimatedCost} credit
          {estimatedCost === 1 ? '' : 's'}
        </Text>
      ) : (
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
          {charCount} chars · sent via WhatsApp
        </Text>
      )}
      <FilterBottomSheet
        visible={pickerVisible}
        title="Add parent recipients"
        onClose={() => setPickerVisible(false)}
        onApply={applyRecipients}
        onClear={() => setPickerClassId(undefined)}
      >
        <Text style={[labelStyle, { marginTop: 0 }]}>Scope</Text>
        <FilterChipRow>
          <FilterChip
            label="Whole school"
            active={pickerClassId == null}
            onPress={() => setPickerClassId(undefined)}
          />
          {(classesQuery.data ?? []).map((cls) => (
            <FilterChip
              key={cls.id}
              label={cls.name}
              active={pickerClassId === cls.id}
              onPress={() => setPickerClassId(cls.id)}
            />
          ))}
        </FilterChipRow>
        <View style={{ marginTop: spacing.md, minHeight: 24 }}>
          {recipientsQuery.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : recipientsQuery.isError ? (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              Could not load recipients. Try again.
            </Text>
          ) : (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {recipientsQuery.data?.total ?? 0} parent contact
              {(recipientsQuery.data?.total ?? 0) === 1 ? '' : 's'} across{' '}
              {recipientsQuery.data?.students_matched ?? 0} student
              {(recipientsQuery.data?.students_matched ?? 0) === 1 ? '' : 's'}. Apply to add them to
              the recipient list.
            </Text>
          )}
        </View>
      </FilterBottomSheet>

      <Pressable
        onPress={() => void onSend()}
        disabled={pending}
        accessibilityRole="button"
        style={({ pressed }) => [
          styles.sendBtn,
          {
            backgroundColor: colors.primary,
            borderRadius: radius.control,
            marginTop: spacing.lg,
            padding: spacing.md,
            opacity: pending ? 0.6 : pressed ? 0.85 : 1,
          },
        ]}
      >
        <Text style={{ color: colors.white, fontWeight: '700', fontSize: typography.body.fontSize }}>
          {pending ? 'Sending…' : channel === 'whatsapp' ? 'Send via WhatsApp' : 'Send SMS'}
        </Text>
      </Pressable>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  input: { borderWidth: StyleSheet.hairlineWidth },
  recipientRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  textArea: { minHeight: 120, textAlignVertical: 'top' },
  sendBtn: { alignItems: 'center' },
});
