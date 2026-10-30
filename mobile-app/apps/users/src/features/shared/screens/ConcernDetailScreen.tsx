import { useConcernDetail, useCurrentUser, usePostConcernMessage, useUpdateConcern } from '@erp/core';
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
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
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
import { formatDateTime } from '../utils/format';
import { showError, showSuccess } from '../utils/feedback';

const STATUSES = ['open', 'in_progress', 'resolved', 'closed'] as const;

type ConcernDetailParams = { ConcernDetail: { concernId: number } };

export const ConcernDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ConcernDetailParams, 'ConcernDetail'>>();
  const concernId = route.params?.concernId ?? 0;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const user = useCurrentUser();
  const canManageStatus = Boolean(user?.staffId);
  const query = useConcernDetail(concernId);
  const postMessage = usePostConcernMessage();
  const updateMutation = useUpdateConcern();
  const [draft, setDraft] = useState('');
  const [status, setStatus] = useState('open');

  useEffect(() => {
    if (query.data?.status) setStatus(query.data.status);
  }, [query.data?.status]);

  const send = async () => {
    const body = draft.trim();
    if (!body) {
      showError('Reply required', 'Write a message before sending.');
      return;
    }
    try {
      await postMessage.mutateAsync({ id: concernId, body });
      setDraft('');
      showSuccess('Sent', 'Your reply was delivered.');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Failed to send reply.');
    }
  };

  const saveStatus = async () => {
    try {
      await updateMutation.mutateAsync({ id: concernId, status });
      showSuccess('Updated', 'Concern status saved.');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Update failed.');
    }
  };

  const canReply = query.data?.can_reply !== false && query.data?.status !== 'closed';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
      >
        <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
          <AcademicScreenHeader
            title="Concern"
            subtitle={query.data?.student_name ?? undefined}
            onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
          />

          {query.isLoading ? <ActivityIndicator color={colors.primary} /> : null}
          {query.isError ? (
            <EmptyState
              title="Could not load"
              message={query.error instanceof Error ? query.error.message : 'Try again.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : null}

          {query.data ? (
            <>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm, marginBottom: spacing.sm }}>
                <Text
                  style={{
                    color: palette.textPrimary,
                    fontWeight: '800',
                    fontSize: typography.title.fontSize,
                    flex: 1,
                    textTransform: 'capitalize',
                  }}
                >
                  {query.data.category}
                </Text>
                <StatusBadge label={query.data.status} tone="info" />
              </View>
              <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
                {query.data.admission_number}
                {query.data.class_name ? ` · ${query.data.class_name}` : ''}
              </Text>
              {(query.data.raised_by_label || query.data.raised_by?.label) ? (
                <Text style={{ color: palette.textMuted, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
                  Raised by {query.data.raised_by_label ?? query.data.raised_by?.label}
                </Text>
              ) : null}

              <SurfaceCard accent="warning">
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: 4 }}>
                  Original concern
                </Text>
                <Text style={{ color: palette.textPrimary }}>{query.data.description}</Text>
                <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
                  {formatDateTime(query.data.created_at)}
                </Text>
              </SurfaceCard>

              <Text
                style={{
                  color: palette.textMuted,
                  fontWeight: '700',
                  marginTop: spacing.lg,
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
                    style={[
                      styles.bubble,
                      {
                        alignSelf: msg.is_staff ? 'flex-start' : 'flex-end',
                        backgroundColor: msg.is_staff ? palette.surface : colors.primary,
                        borderColor: palette.border,
                        borderRadius: radius.md,
                        padding: spacing.sm,
                        marginBottom: spacing.sm,
                        maxWidth: '88%',
                      },
                    ]}
                  >
                    <Text
                      style={{
                        color: msg.is_staff ? palette.textMuted : 'rgba(255,255,255,0.85)',
                        fontSize: typography.caption.fontSize,
                        marginBottom: 4,
                        fontWeight: '600',
                      }}
                    >
                      {msg.is_staff ? 'School' : 'Parent'}
                      {msg.author_name ? ` · ${msg.author_name}` : ''}
                    </Text>
                    <Text style={{ color: msg.is_staff ? palette.textPrimary : '#fff' }}>{msg.body}</Text>
                    <Text
                      style={{
                        color: msg.is_staff ? palette.textMuted : 'rgba(255,255,255,0.75)',
                        fontSize: typography.caption.fontSize,
                        marginTop: 4,
                      }}
                    >
                      {formatDateTime(msg.created_at)}
                    </Text>
                  </View>
                ))
              )}

              {canManageStatus ? (
                <View style={{ marginTop: spacing.md }}>
                  <FilterChipRow label="Status">
                    {STATUSES.map((s) => (
                      <FilterChip key={s} label={s.replace('_', ' ')} active={status === s} onPress={() => setStatus(s)} />
                    ))}
                  </FilterChipRow>
                  <Button
                    label="Save status"
                    onPress={() => void saveStatus()}
                    loading={updateMutation.isPending}
                    style={{ marginTop: spacing.sm }}
                  />
                </View>
              ) : null}
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
              placeholder="Write a reply…"
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

        {query.data && !canReply ? (
          <View style={{ padding: spacing.md }}>
            <Text style={{ color: palette.textMuted, textAlign: 'center' }}>This concern is closed.</Text>
          </View>
        ) : null}
      </KeyboardAvoidingView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  bubble: {
    borderWidth: StyleSheet.hairlineWidth,
  },
});
