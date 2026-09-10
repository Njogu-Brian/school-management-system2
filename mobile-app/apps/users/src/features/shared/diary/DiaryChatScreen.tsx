import { useDiaryThread, useSendDiaryMessage, type DiaryChannel, type DiaryEntryRecord } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import * as DocumentPicker from 'expo-document-picker';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  FlatList,
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { showError, showSuccess } from '../utils/feedback';

type AttachmentDraft = { uri: string; name: string; type: string };

type DiaryChatParams = {
  DiaryChat: { studentId: number; studentName?: string; channel?: DiaryChannel };
};

function formatMessageTime(value?: string | null): string {
  if (!value) return '';
  return value.slice(0, 16).replace('T', ' ');
}

function attachmentLabel(urlOrName: string): string {
  try {
    const path = urlOrName.split('?')[0];
    const segment = path.split('/').pop();
    return decodeURIComponent(segment || urlOrName);
  } catch {
    return urlOrName;
  }
}

function entryAttachments(item: DiaryEntryRecord): string[] {
  const urls = item.attachment_urls ?? [];
  const names = item.attachments ?? [];
  return [...urls, ...names].filter(Boolean);
}

export const DiaryChatScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<DiaryChatParams, 'DiaryChat'>>();
  const { studentId, studentName } = route.params;
  const [channel, setChannel] = useState<DiaryChannel>(route.params.channel ?? 'teacher_parent');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const threadQuery = useDiaryThread(studentId, { channel });
  const sendMutation = useSendDiaryMessage(studentId, channel);
  const [draft, setDraft] = useState('');
  const [attachments, setAttachments] = useState<AttachmentDraft[]>([]);
  const listRef = useRef<FlatList>(null);

  const entries = useMemo(() => threadQuery.data?.entries ?? [], [threadQuery.data]);
  const title = threadQuery.data?.student_name ?? studentName ?? `Student #${studentId}`;
  const channelLabel = channel === 'admin_parent' ? 'Admin only' : 'Class teacher / admin';

  useEffect(() => {
    if (entries.length === 0) return;
    const t = setTimeout(() => listRef.current?.scrollToEnd({ animated: true }), 120);
    return () => clearTimeout(t);
  }, [entries.length]);

  const pickAttachment = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        copyToCacheDirectory: true,
        multiple: true,
      });
      if (result.canceled || !result.assets?.length) return;
      setAttachments((prev) => [
        ...prev,
        ...result.assets.map((a) => ({
          uri: a.uri,
          name: a.name ?? 'file',
          type: a.mimeType ?? 'application/octet-stream',
        })),
      ]);
    } catch (err) {
      showError('Attachment failed', err instanceof Error ? err.message : 'Could not attach file.');
    }
  };

  const send = async () => {
    const content = draft.trim();
    if (!content && attachments.length === 0) return;
    try {
      await sendMutation.mutateAsync({
        content: content || '(attachment)',
        attachments: attachments.length ? attachments : undefined,
      });
      setDraft('');
      setAttachments([]);
      showSuccess('Sent');
    } catch (err) {
      showError('Send failed', err instanceof Error ? err.message : 'Could not send.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title={title}
          subtitle={`${channelLabel} conversation`}
          onBack={() => navigation.goBack()}
        />
        <FilterChipRow label="Send to">
          <FilterChip
            label="Class teacher / admin"
            active={channel === 'teacher_parent'}
            onPress={() => setChannel('teacher_parent')}
          />
          <FilterChip
            label="Admin only"
            active={channel === 'admin_parent'}
            onPress={() => setChannel('admin_parent')}
          />
        </FilterChipRow>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
          {channel === 'admin_parent'
            ? 'Admin only — teachers cannot read this thread.'
            : 'Class teacher / admin — your class teacher and authorized school staff see this thread.'}
        </Text>
      </View>

      {threadQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : threadQuery.isError ? (
        <EmptyState
          title="Could not load conversation"
          message={threadQuery.error instanceof Error ? threadQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void threadQuery.refetch()}
        />
      ) : (
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          keyboardVerticalOffset={88}
        >
          <FlatList
            ref={listRef}
            data={entries}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.lg, flexGrow: 1 }}
            ListEmptyComponent={
              <EmptyState
                title="No messages yet"
                message={`Start the ${channelLabel.toLowerCase()} conversation.`}
                icon="chatbubbles-outline"
              />
            }
            renderItem={({ item }) => {
              const mine = item.is_mine;
              const files = entryAttachments(item);
              return (
                <View
                  style={[
                    styles.bubble,
                    {
                      alignSelf: mine ? 'flex-end' : 'flex-start',
                      backgroundColor: mine ? colors.primary : palette.surfaceRaised,
                      borderColor: palette.borderSubtle,
                      borderRadius: radius.lg,
                      maxWidth: '86%',
                      marginBottom: spacing.sm,
                      padding: spacing.sm,
                    },
                  ]}
                >
                  {!mine ? (
                    <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: 2 }}>
                      {item.author_name ?? item.author_type}
                    </Text>
                  ) : null}
                  <Text style={{ color: mine ? '#fff' : palette.textPrimary }}>{item.content}</Text>
                  {files.map((f) => (
                    <Pressable key={f} onPress={() => void Linking.openURL(f)} style={{ marginTop: 6 }}>
                      <Text style={{ color: mine ? '#E0F2FE' : colors.primary, textDecorationLine: 'underline' }}>
                        {attachmentLabel(f)}
                      </Text>
                    </Pressable>
                  ))}
                  <Text
                    style={{
                      color: mine ? 'rgba(255,255,255,0.75)' : palette.textMuted,
                      fontSize: typography.caption.fontSize,
                      marginTop: 4,
                      alignSelf: 'flex-end',
                    }}
                  >
                    {formatMessageTime(item.created_at)}
                  </Text>
                </View>
              );
            }}
          />

          {attachments.length > 0 ? (
            <View style={{ paddingHorizontal: spacing.md, gap: spacing.xs }}>
              {attachments.map((a) => (
                <Text key={a.uri} style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {a.name}
                </Text>
              ))}
            </View>
          ) : null}

          <View
            style={{
              flexDirection: 'row',
              alignItems: 'flex-end',
              gap: spacing.sm,
              padding: spacing.md,
              borderTopWidth: StyleSheet.hairlineWidth,
              borderTopColor: palette.border,
            }}
          >
            <Pressable onPress={() => void pickAttachment()} hitSlop={8} accessibilityLabel="Attach file">
              <Ionicons name="attach" size={24} color={colors.primary} />
            </Pressable>
            <TextInput
              value={draft}
              onChangeText={setDraft}
              placeholder={`Message ${channelLabel.toLowerCase()}…`}
              placeholderTextColor={palette.textMuted}
              multiline
              style={{
                flex: 1,
                minHeight: 44,
                maxHeight: 120,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.md,
                paddingHorizontal: spacing.sm,
                paddingVertical: spacing.sm,
                color: palette.textPrimary,
                backgroundColor: palette.surface,
              }}
            />
            <Button label="Send" onPress={() => void send()} loading={sendMutation.isPending} />
          </View>
        </KeyboardAvoidingView>
      )}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  bubble: {
    borderWidth: StyleSheet.hairlineWidth,
  },
});
