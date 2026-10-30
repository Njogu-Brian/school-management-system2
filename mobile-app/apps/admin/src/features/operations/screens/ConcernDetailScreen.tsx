import { useConcernDetail, usePostConcernMessage, useUpdateConcern } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  StatusBadge,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'ConcernDetail'>;

const STATUSES = ['open', 'in_progress', 'resolved', 'closed'] as const;

export const ConcernDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { concernId } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useConcernDetail(concernId);
  const updateMutation = useUpdateConcern();
  const postMessage = usePostConcernMessage();
  const [status, setStatus] = useState('open');
  const [draft, setDraft] = useState('');

  useEffect(() => {
    if (query.data?.status) setStatus(query.data.status);
  }, [query.data?.status]);

  const save = async () => {
    try {
      await updateMutation.mutateAsync({ id: concernId, status });
      showSuccess('Updated', 'Concern status saved.');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Update failed.');
    }
  };

  const send = async () => {
    const body = draft.trim();
    if (!body) {
      showError('Reply required', 'Write a message before sending.');
      return;
    }
    try {
      await postMessage.mutateAsync({ id: concernId, body });
      setDraft('');
      showSuccess('Sent', 'Reply delivered to the parent.');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Failed to send reply.');
    }
  };

  const canReply = query.data?.can_reply !== false && query.data?.status !== 'closed';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
          <AcademicScreenHeader title="Concern detail" onBack={() => navigation.goBack()} />
          {query.isLoading ? <ActivityIndicator color={palette.primary} /> : null}
          {query.isError ? (
            <EmptyState
              title="Could not load"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : query.data ? (
            <>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm }}>
                <Text
                  style={{
                    color: palette.textPrimary,
                    fontWeight: '800',
                    fontSize: typography.title.fontSize,
                    flex: 1,
                  }}
                >
                  {query.data.student_name}
                </Text>
                <StatusBadge label={query.data.status} tone="info" />
              </View>
              <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
                {query.data.category} · {query.data.admission_number}
              </Text>
              {(query.data.raised_by_label || query.data.raised_by?.label) ? (
                <Text style={{ color: palette.textMuted, marginBottom: spacing.md }}>
                  Raised by {query.data.raised_by_label ?? query.data.raised_by?.label}
                </Text>
              ) : null}

              <SurfaceCard accent="warning">
                <Text style={{ color: palette.textMuted, marginBottom: 4, fontSize: typography.caption.fontSize }}>
                  Original concern
                </Text>
                <Text style={{ color: palette.textPrimary }}>{query.data.description}</Text>
              </SurfaceCard>

              <Text style={{ color: palette.textMuted, marginTop: spacing.md, marginBottom: spacing.sm }}>
                Staff: {query.data.staff.map((s) => s.name).filter(Boolean).join(', ') || '—'}
              </Text>

              <Text
                style={{
                  color: palette.textMuted,
                  fontWeight: '700',
                  marginTop: spacing.md,
                  marginBottom: spacing.sm,
                  textTransform: 'uppercase',
                  fontSize: typography.caption.fontSize,
                }}
              >
                Conversation
              </Text>

              {(query.data.messages ?? []).length === 0 ? (
                <Text style={{ color: palette.textMuted, marginBottom: spacing.md }}>No replies yet.</Text>
              ) : (
                (query.data.messages ?? []).map((msg) => (
                  <View
                    key={msg.id}
                    style={{
                      alignSelf: msg.is_staff ? 'flex-end' : 'flex-start',
                      backgroundColor: msg.is_staff ? colors.primary : palette.surface,
                      borderColor: palette.border,
                      borderWidth: StyleSheet.hairlineWidth,
                      borderRadius: radius.md,
                      padding: spacing.sm,
                      marginBottom: spacing.sm,
                      maxWidth: '88%',
                    }}
                  >
                    <Text
                      style={{
                        color: msg.is_staff ? 'rgba(255,255,255,0.85)' : palette.textMuted,
                        fontSize: typography.caption.fontSize,
                        marginBottom: 4,
                        fontWeight: '600',
                      }}
                    >
                      {msg.is_staff ? 'School' : 'Parent'}
                      {msg.author_name ? ` · ${msg.author_name}` : ''}
                    </Text>
                    <Text style={{ color: msg.is_staff ? '#fff' : palette.textPrimary }}>{msg.body}</Text>
                  </View>
                ))
              )}

              <FilterChipRow label="Status">
                {STATUSES.map((s) => (
                  <FilterChip key={s} label={s.replace('_', ' ')} active={status === s} onPress={() => setStatus(s)} />
                ))}
              </FilterChipRow>
              <Button
                label="Save status"
                onPress={() => void save()}
                loading={updateMutation.isPending}
                style={{ marginTop: spacing.sm, marginBottom: spacing.lg }}
              />
            </>
          ) : null}
        </ScrollView>

        {query.data && canReply ? (
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
            <TextInput
              value={draft}
              onChangeText={setDraft}
              placeholder="Reply to parent…"
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
            <Button label="Send" onPress={() => void send()} loading={postMessage.isPending} />
          </View>
        ) : null}
      </KeyboardAvoidingView>
    </ScreenContainer>
  );
};
