import { useDiaryThread, useSendDiaryMessage, type DiaryEntryRecord } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
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
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { showError, showSuccess } from '../utils/feedback';

type AttachmentDraft = { uri: string; name: string; type: string };

type DiaryChatParams = {
  DiaryChat: { studentId: number; studentName?: string };
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
  const { colors, palette, spacing, typography, radius } = useTheme();
  const insets = useSafeAreaInsets();
  const threadQuery = useDiaryThread(studentId);
  const sendMutation = useSendDiaryMessage(studentId);
  const [draft, setDraft] = useState('');
  const [attachments, setAttachments] = useState<AttachmentDraft[]>([]);
  const listRef = useRef<FlatList>(null);

  const entries = useMemo(() => threadQuery.data?.entries ?? [], [threadQuery.data]);
  const title = threadQuery.data?.student_name ?? studentName ?? `Student #${studentId}`;

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
        ...result.assets.map((asset) => ({
          uri: asset.uri,
          name: asset.name || 'attachment',
          type: asset.mimeType || 'application/octet-stream',
        })),
      ]);
    } catch (err) {
      showError('Attachment failed', err instanceof Error ? err.message : 'Could not pick file.');
    }
  };

  const send = async () => {
    const content = draft.trim();
    if (!content && attachments.length === 0) return;
    try {
      await sendMutation.mutateAsync({
        content: content || '(attachment)',
        attachments: attachments.length > 0 ? attachments : undefined,
      });
      setDraft('');
      setAttachments([]);
      showSuccess('Sent', 'Message posted to diary.');
    } catch (err) {
      showError('Send failed', err instanceof Error ? err.message : 'Could not send message.');
    }
  };

  const canSend = draft.trim().length > 0 || attachments.length > 0;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title={title}
          subtitle={threadQuery.data?.class_name ?? 'Diary conversation'}
          onBack={() => navigation.goBack()}
        />
      </View>

      {threadQuery.isLoading && entries.length === 0 ? (
        <View style={{ padding: spacing.md }}>
          <SkeletonListRows variant="compact" count={4} />
        </View>
      ) : threadQuery.isError ? (
        <EmptyState
          title="Could not load thread"
          message={(threadQuery.error as Error)?.message ?? 'Something went wrong.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void threadQuery.refetch()}
        />
      ) : (
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
        >
          <FlatList
            ref={listRef}
            data={entries}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{
              paddingHorizontal: spacing.md,
              paddingBottom: spacing.md,
              flexGrow: 1,
              justifyContent: entries.length === 0 ? 'center' : 'flex-end',
            }}
            ListEmptyComponent={
              <EmptyState
                title="No messages yet"
                message="Write the first diary note for this student."
                icon="chatbubble-ellipses-outline"
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
                      backgroundColor: mine ? colors.primary : palette.surface,
                      borderColor: mine ? colors.primary : palette.border,
                      borderRadius: radius.lg,
                      borderBottomRightRadius: mine ? 4 : radius.lg,
                      borderBottomLeftRadius: mine ? radius.lg : 4,
                      paddingHorizontal: spacing.md,
                      paddingVertical: spacing.sm,
                      marginBottom: spacing.sm,
                      maxWidth: '88%',
                    },
                  ]}
                >
                  {!mine && item.author_name ? (
                    <Text
                      style={{
                        color: colors.primary,
                        fontSize: typography.caption.fontSize,
                        fontWeight: '700',
                        marginBottom: 2,
                      }}
                    >
                      {item.author_name}
                    </Text>
                  ) : null}
                  {item.content && item.content !== '(attachment)' ? (
                    <Text style={{ color: mine ? '#fff' : palette.textPrimary }}>{item.content}</Text>
                  ) : null}
                  {files.length > 0 ? (
                    <View style={{ marginTop: item.content && item.content !== '(attachment)' ? spacing.xs : 0, gap: 4 }}>
                      {files.map((file) => (
                        <Pressable
                          key={file}
                          onPress={() => {
                            if (file.startsWith('http')) void Linking.openURL(file);
                          }}
                          style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}
                        >
                          <Ionicons name="document-attach-outline" size={14} color={mine ? '#ffffffcc' : colors.primary} />
                          <Text
                            style={{
                              color: mine ? '#ffffffcc' : palette.textSecondary,
                              fontSize: typography.caption.fontSize,
                              textDecorationLine: file.startsWith('http') ? 'underline' : 'none',
                            }}
                            numberOfLines={2}
                          >
                            {attachmentLabel(file)}
                          </Text>
                        </Pressable>
                      ))}
                    </View>
                  ) : null}
                  <Text
                    style={{
                      color: mine ? '#ffffff99' : palette.textMuted,
                      fontSize: 11,
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
            <View style={{ paddingHorizontal: spacing.md, paddingBottom: spacing.xs, gap: 4 }}>
              {attachments.map((file) => (
                <View
                  key={file.uri}
                  style={{
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    backgroundColor: palette.surfaceRaised ?? palette.surface,
                    borderRadius: radius.md,
                    borderWidth: StyleSheet.hairlineWidth,
                    borderColor: palette.border,
                    padding: spacing.sm,
                  }}
                >
                  <Ionicons name="document-outline" size={18} color={colors.primary} />
                  <Text style={{ color: palette.textSecondary, flex: 1, marginHorizontal: spacing.sm }} numberOfLines={1}>
                    {file.name}
                  </Text>
                  <Pressable
                    onPress={() => setAttachments((prev) => prev.filter((a) => a.uri !== file.uri))}
                    hitSlop={8}
                  >
                    <Ionicons name="close-circle" size={20} color={palette.textMuted} />
                  </Pressable>
                </View>
              ))}
            </View>
          ) : null}

          <View
            style={{
              flexDirection: 'row',
              alignItems: 'flex-end',
              gap: spacing.sm,
              paddingHorizontal: spacing.md,
              paddingTop: spacing.sm,
              paddingBottom: Math.max(insets.bottom, spacing.sm),
              borderTopWidth: StyleSheet.hairlineWidth,
              borderTopColor: palette.border,
              backgroundColor: palette.surface,
            }}
          >
            <Pressable
              onPress={() => void pickAttachment()}
              style={{ width: 40, height: 40, alignItems: 'center', justifyContent: 'center' }}
              accessibilityLabel="Attach file"
            >
              <Ionicons name="attach" size={24} color={colors.primary} />
            </Pressable>
            <TextInput
              value={draft}
              onChangeText={setDraft}
              placeholder="Write a diary note…"
              placeholderTextColor={palette.textMuted}
              multiline
              style={{
                flex: 1,
                minHeight: 40,
                maxHeight: 120,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.lg,
                paddingHorizontal: spacing.md,
                paddingVertical: Platform.OS === 'ios' ? spacing.sm : spacing.xs,
                color: palette.textPrimary,
                backgroundColor: palette.surfaceRaised,
              }}
            />
            <Button
              label="Send"
              onPress={() => void send()}
              loading={sendMutation.isPending}
              disabled={!canSend}
              style={{ minWidth: 72 }}
            />
          </View>
        </KeyboardAvoidingView>
      )}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  bubble: { borderWidth: StyleSheet.hairlineWidth },
});
