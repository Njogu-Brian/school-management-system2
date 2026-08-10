import {
  useCan,
  useCommunicationTemplates,
  useSendApp,
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
import { ActivityIndicator, Pressable, StyleSheet, Switch, Text, TextInput, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'SmsCompose'>;

const SMS_SEGMENT = 160;
type SenderId = 'default' | 'finance';
type Channel = 'sms' | 'whatsapp' | 'email' | 'app';
type AppTarget = 'parents' | 'staff' | 'class';

export const SmsComposeScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const [channel, setChannel] = useState<Channel>('sms');
  const templateType = channel === 'email' ? 'email' : channel === 'app' ? 'sms' : channel;
  const templatesQuery = useCommunicationTemplates({ enabled: canView, type: templateType });
  const sendMutation = useSendSms();
  const sendWhatsAppMutation = useSendWhatsApp();
  const sendEmailMutation = useSendEmail();
  const sendAppMutation = useSendApp();
  const [message, setMessage] = useState('');
  const [subject, setSubject] = useState('');
  const [phones, setPhones] = useState('');
  const [systemRecipientCount, setSystemRecipientCount] = useState(0);
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | undefined>();
  const [senderId, setSenderId] = useState<SenderId>('default');
  const [pickerVisible, setPickerVisible] = useState(false);
  const [pickerClassId, setPickerClassId] = useState<number | undefined>();
  const [alsoNotifyApp, setAlsoNotifyApp] = useState(false);
  const [appTarget, setAppTarget] = useState<AppTarget>('parents');

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
    sendMutation.isPending ||
    sendWhatsAppMutation.isPending ||
    sendEmailMutation.isPending ||
    sendAppMutation.isPending;

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

    try {
      if (channel === 'app') {
        if (appTarget === 'class' && !pickerClassId) {
          showError('Select a class', 'Choose a classroom for app notifications.');
          return;
        }
        const res = await sendAppMutation.mutateAsync({
          title: subject.trim() || 'School update',
          message: message.trim() || undefined,
          template_id: selectedTemplateId,
          target: appTarget,
          classroom_id: appTarget === 'class' ? pickerClassId : undefined,
        });
        showSuccess('App notify sent', res.message ?? `Notified ${res.data?.notified ?? 0}`, () =>
          navigation.goBack(),
        );
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

      if (alsoNotifyApp && pickerClassId) {
        try {
          await sendAppMutation.mutateAsync({
            title: subject.trim() || 'School update',
            message: message.trim() || undefined,
            template_id: selectedTemplateId,
            target: 'class',
            classroom_id: pickerClassId,
          });
        } catch {
          /* primary channel already succeeded */
        }
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
        subtitle="SMS, WhatsApp, email, or in-app notification"
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
        {(
          [
            { id: 'sms' as const, label: 'SMS' },
            { id: 'whatsapp' as const, label: 'WA' },
            { id: 'email' as const, label: 'Email' },
            { id: 'app' as const, label: 'App' },
          ] as const
        ).map((tab) => {
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
          <FilterChipRow>
            <FilterChip
              label="None"
              active={selectedTemplateId == null}
              onPress={() => setSelectedTemplateId(undefined)}
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

      {channel === 'email' || channel === 'app' ? (
        <>
          <Text style={labelStyle}>{channel === 'app' ? 'Title' : 'Subject'}</Text>
          <TextInput
            value={subject}
            onChangeText={setSubject}
            placeholder={channel === 'app' ? 'Notification title' : 'Email subject'}
            placeholderTextColor={palette.textMuted}
            style={inputStyle}
          />
        </>
      ) : null}

      {channel === 'app' ? (
        <>
          <Text style={labelStyle}>Audience</Text>
          <FilterChipRow>
            <FilterChip label="All parents" active={appTarget === 'parents'} onPress={() => setAppTarget('parents')} />
            <FilterChip label="Staff" active={appTarget === 'staff'} onPress={() => setAppTarget('staff')} />
            <FilterChip label="Class" active={appTarget === 'class'} onPress={() => setAppTarget('class')} />
          </FilterChipRow>
          {appTarget === 'class' ? (
            <>
              <Text style={labelStyle}>Classroom</Text>
              <FilterChipRow>
                {(classesQuery.data ?? []).map((c) => (
                  <FilterChip
                    key={c.id}
                    label={c.name}
                    active={pickerClassId === c.id}
                    onPress={() => setPickerClassId(c.id)}
                  />
                ))}
              </FilterChipRow>
            </>
          ) : null}
        </>
      ) : (
        <>
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

          <View
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'space-between',
              marginTop: spacing.md,
              paddingVertical: spacing.sm,
            }}
          >
            <View style={{ flex: 1, paddingRight: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>Also notify in app</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                Requires class picker selection so linked parent accounts can be resolved.
              </Text>
            </View>
            <Switch value={alsoNotifyApp} onValueChange={setAlsoNotifyApp} />
          </View>
        </>
      )}

      <Text style={labelStyle}>Message</Text>
      <TextInput
        value={message}
        onChangeText={setMessage}
        multiline
        placeholder="Message body"
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
          {charCount} chars · {channel === 'app' ? 'in-app + push' : `sent via ${channel}`}
        </Text>
      )}

      <Pressable
        onPress={() => void onSend()}
        disabled={pending}
        style={[
          styles.sendBtn,
          {
            backgroundColor: colors.primary,
            borderRadius: radius.control,
            opacity: pending ? 0.7 : 1,
            marginTop: spacing.lg,
          },
        ]}
      >
        {pending ? (
          <ActivityIndicator color={colors.white} />
        ) : (
          <Text style={{ color: colors.white, fontWeight: '700' }}>
            {channel === 'app' ? 'Send app notification' : 'Send'}
          </Text>
        )}
      </Pressable>

      <FilterBottomSheet
        visible={pickerVisible}
        title="Add parent recipients"
        onClose={() => setPickerVisible(false)}
        onApply={applyRecipients}
        onClear={() => setPickerClassId(undefined)}
      >
        <Text style={[labelStyle, { marginTop: 0 }]}>Scope</Text>
        <FilterChipRow>
          {(classesQuery.data ?? []).map((c) => (
            <FilterChip
              key={c.id}
              label={c.name}
              active={pickerClassId === c.id}
              onPress={() => setPickerClassId(c.id)}
            />
          ))}
        </FilterChipRow>
        {recipientsQuery.isLoading ? <ActivityIndicator style={{ marginTop: spacing.md }} /> : null}
        {recipientsQuery.data ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>
            {recipientsQuery.data.total} contacts · {recipientsQuery.data.students_matched} students
          </Text>
        ) : null}
      </FilterBottomSheet>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flexGrow: 1, justifyContent: 'center' },
  channelTabs: { flexDirection: 'row', borderWidth: StyleSheet.hairlineWidth },
  channelTab: { flex: 1, alignItems: 'center', paddingVertical: 10 },
  input: { borderWidth: StyleSheet.hairlineWidth, minHeight: 48 },
  textArea: { minHeight: 120, textAlignVertical: 'top' },
  recipientRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sendBtn: { minHeight: 48, alignItems: 'center', justifyContent: 'center' },
});
