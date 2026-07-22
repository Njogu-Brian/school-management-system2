import {
  useCan,
  useCommunicationTemplates,
  useSendEmail,
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
type Channel = 'sms' | 'whatsapp' | 'email';

export const SmsComposeScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [channel, setChannel] = useState<Channel>('sms');
  const templateType = channel === 'email' ? 'email' : channel;
  const templatesQuery = useCommunicationTemplates({ enabled: canView, type: templateType });
  const sendMutation = useSendSms();
  const sendWhatsAppMutation = useSendWhatsApp();
  const sendEmailMutation = useSendEmail();
  const [message, setMessage] = useState('');
  const [subject, setSubject] = useState('');
  const [phones, setPhones] = useState('');
  const [systemRecipientCount, setSystemRecipientCount] = useState(0);
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | undefined>();
  const [senderId, setSenderId] = useState<SenderId>('default');
  const [pickerVisible, setPickerVisible] = useState(false);
  const [pickerClassId, setPickerClassId] = useState<number | undefined>();

  const classesQuery = useSettingsClasses({ enabled: canView });
  const recipientsQuery = useSmsRecipients({
    enabled: canView && pickerVisible,
    classroomId: pickerClassId,
    channel: channel === 'email' ? 'email' : 'sms',
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
    setSystemRecipientCount((prev) => prev + fetched.length);
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

  const pending =
    sendMutation.isPending || sendWhatsAppMutation.isPending || sendEmailMutation.isPending;

  const switchChannel = (next: Channel) => {
    setChannel(next);
    setSelectedTemplateId(undefined);
    setSystemRecipientCount(0);
  };

  const onSend = async () => {
    if (!message.trim() && !selectedTemplateId) {
      showError('Missing fields', 'Enter a message or select a template.');
      return;
    }
    if (!phones.trim()) {
      showError(
        'Missing fields',
        channel === 'email' ? 'Enter at least one email address.' : 'Enter at least one phone number.',
      );
      return;
    }
    if (selectedTemplateId && systemRecipientCount <= 0) {
      showError(
        'System recipients required',
        'When using a template, add recipients via class picker (system contacts). Custom numbers alone are not allowed.',
      );
      return;
    }
    try {
      const fromSystem = systemRecipientCount > 0 ? true : undefined;
      let res;
      if (channel === 'whatsapp') {
        res = await sendWhatsAppMutation.mutateAsync({
          message: message.trim() || undefined,
          template_id: selectedTemplateId,
          custom_numbers: phones.trim(),
          from_system_recipients: fromSystem,
        });
      } else if (channel === 'email') {
        res = await sendEmailMutation.mutateAsync({
          subject: subject.trim() || undefined,
          message: message.trim() || undefined,
          template_id: selectedTemplateId,
          custom_emails: phones.trim(),
          from_system_recipients: fromSystem,
        });
      } else {
        res = await sendMutation.mutateAsync({
          message: message.trim() || undefined,
          template_id: selectedTemplateId,
          custom_numbers: phones.trim(),
          sender_id: senderId,
          from_system_recipients: fromSystem,
        });
      }
      const channelLabel = channel === 'whatsapp' ? 'WhatsApp' : channel === 'email' ? 'Email' : 'SMS';
      showSuccess(
        `${channelLabel} sent`,
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
        subtitle="Broadcast via SMS, WhatsApp, or email"
        onBack={() => navigation.goBack()}
      />

      <Text style={[labelStyle, { marginTop: 0 }]}>Channel</Text>
      <View
        style={[
          styles.channelTabs,
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            borderRadius: radius.control,
            padding: 4,
          },
        ]}
      >
        {([
          { id: 'sms' as const, label: 'SMS' },
          { id: 'whatsapp' as const, label: 'WhatsApp' },
          { id: 'email' as const, label: 'Email' },
        ]).map((tab) => {
          const active = channel === tab.id;
          return (
            <Pressable
              key={tab.id}
              onPress={() => switchChannel(tab.id)}
              style={[
                styles.channelTab,
                {
                  backgroundColor: active ? colors.primary : 'transparent',
                  borderRadius: radius.md,
                },
              ]}
            >
              <Text
                style={{
                  color: active ? colors.white : palette.textSecondary,
                  fontWeight: '700',
                  fontSize: typography.caption.fontSize,
                }}
              >
                {tab.label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {templatesQuery.data && templatesQuery.data.length > 0 ? (
        <View style={{ marginBottom: spacing.sm }}>
          <Text style={labelStyle}>Template</Text>
          {selectedTemplateId ? (
            <Text style={{ color: colors.warning, fontSize: typography.caption.fontSize, marginBottom: spacing.xs }}>
              Template selected — add system recipients via class picker before sending.
            </Text>
          ) : null}
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
                  if (tpl.subject) setSubject(tpl.subject);
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

      {channel === 'email' ? (
        <>
          <Text style={labelStyle}>Subject</Text>
          <TextInput
            value={subject}
            onChangeText={setSubject}
            placeholder="Email subject"
            placeholderTextColor={palette.textMuted}
            style={inputStyle}
          />
        </>
      ) : null}

      <Text style={labelStyle}>
        {channel === 'email' ? 'Email addresses (comma-separated)' : 'Phone numbers (comma-separated)'}
      </Text>
      <TextInput
        value={phones}
        onChangeText={(t) => {
          setPhones(t);
          if (!t.trim()) setSystemRecipientCount(0);
        }}
        placeholder={channel === 'email' ? 'parent@example.com' : '2547XXXXXXXX, 2541XXXXXXXX'}
        placeholderTextColor={palette.textMuted}
        keyboardType={channel === 'email' ? 'email-address' : 'phone-pad'}
        autoCapitalize="none"
        style={inputStyle}
      />
      <View style={[styles.recipientRow, { marginTop: spacing.xs }]}>
        {recipientCount > 0 ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            {recipientCount} recipient{recipientCount === 1 ? '' : 's'}
            {systemRecipientCount > 0 ? ` · ${systemRecipientCount} from system` : ''}
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
        placeholder={channel === 'email' ? 'Email body' : 'Message body'}
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
          {charCount} chars · sent via {channel === 'whatsapp' ? 'WhatsApp' : 'email'}
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
          {pending
            ? 'Sending…'
            : channel === 'whatsapp'
              ? 'Send via WhatsApp'
              : channel === 'email'
                ? 'Send email'
                : 'Send SMS'}
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
  channelTabs: {
    flexDirection: 'row',
    borderWidth: StyleSheet.hairlineWidth,
    gap: 4,
  },
  channelTab: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 10,
  },
});
