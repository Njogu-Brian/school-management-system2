import {
  useAnnouncement,
  useCan,
  useDeleteAnnouncement,
  useUpdateAnnouncement,
} from '@erp/core';
import { AcademicScreenHeader, EmptyState, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { capitalizeStatus, formatDateLabel, formatDateTimeLabel } from '../../shared/utils/formatters';
import { confirmAction, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'AnnouncementDetail'>;

export const AnnouncementDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { announcementId } = route.params;
  const canEdit = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useAnnouncement(announcementId, { enabled: canEdit });
  const updateMutation = useUpdateAnnouncement();
  const deleteMutation = useDeleteAnnouncement();

  const announcement = query.data;

  const togglePublish = useCallback(() => {
    if (!announcement) return;
    const nextActive = !announcement.active;
    const label = nextActive ? 'Publish' : 'Unpublish';
    confirmAction(label, `${label} "${announcement.title}"?`, label, async () => {
      await updateMutation.mutateAsync({
        id: announcement.id,
        title: announcement.title,
        content: announcement.content,
        active: nextActive,
        expires_at: announcement.expires_at,
      });
      showSuccess(`${label}ed`, undefined, () => void query.refetch());
    });
  }, [announcement, query, updateMutation]);

  const onDelete = useCallback(() => {
    if (!announcement) return;
    confirmAction(
      'Delete announcement',
      `Permanently delete "${announcement.title}"?`,
      'Delete',
      async () => {
        await deleteMutation.mutateAsync(announcement.id);
        showSuccess('Deleted', 'Announcement removed.', () => navigation.goBack());
      },
      true,
    );
  }, [announcement, deleteMutation, navigation]);

  if (!canEdit) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need communication.view permission to view this announcement."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (query.isError || !announcement) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <EmptyState
          title="Announcement not found"
          message={(query.error as Error)?.message ?? 'This announcement could not be loaded.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      </ScreenContainer>
    );
  }

  const statusLabel = announcement.active
    ? announcement.expires_at && new Date(announcement.expires_at) < new Date()
      ? 'Expired'
      : 'Published'
    : 'Draft';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Announcement" onBack={() => navigation.goBack()} />

        <FinanceFieldSection
          title="Details"
          rows={[
            { label: 'Status', value: capitalizeStatus(statusLabel) },
            { label: 'Created', value: formatDateTimeLabel(announcement.created_at) },
            { label: 'Expires', value: formatDateLabel(announcement.expires_at) },
          ]}
        />

        <View
          style={[
            styles.card,
            {
              borderColor: palette.border,
              marginTop: spacing.md,
              borderRadius: radius.card,
              padding: spacing.md,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: typography.title.fontWeight,
              fontSize: typography.title.fontSize,
            }}
          >
            {announcement.title}
          </Text>
          <Text
            style={{
              color: palette.textSecondary,
              marginTop: spacing.sm,
              lineHeight: typography.body.lineHeight,
              fontSize: typography.body.fontSize,
            }}
          >
            {announcement.content}
          </Text>
        </View>

        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.lg }}>
          <Pressable
            onPress={() => navigation.navigate('AnnouncementForm', { announcementId: announcement.id })}
            style={[
              styles.btn,
              {
                borderColor: colors.primary,
                borderRadius: radius.control,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.sm,
              },
            ]}
          >
            <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.button.fontSize }}>
              Edit
            </Text>
          </Pressable>
          <Pressable
            onPress={togglePublish}
            style={[
              styles.btn,
              {
                borderColor: colors.primary,
                borderRadius: radius.control,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.sm,
              },
            ]}
          >
            <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.button.fontSize }}>
              {announcement.active ? 'Unpublish' : 'Publish'}
            </Text>
          </Pressable>
          <Pressable
            onPress={onDelete}
            style={[
              styles.btn,
              {
                borderColor: colors.error,
                borderRadius: radius.control,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.sm,
              },
            ]}
          >
            <Text style={{ color: colors.error, fontWeight: '600', fontSize: typography.button.fontSize }}>
              Delete
            </Text>
          </Pressable>
        </View>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: { borderWidth: StyleSheet.hairlineWidth },
  btn: { borderWidth: 1 },
});
