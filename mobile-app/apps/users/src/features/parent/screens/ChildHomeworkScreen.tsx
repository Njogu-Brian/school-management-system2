import {
  useHomeworkCompletion,
  useHomeworkDiaryStatus,
  type HomeworkAttachment,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React from 'react';
import { Linking, Pressable, Share, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { useChildHomework, type HomeworkAssignment } from '../hooks/useChildHomework';
import { showError } from '../../shared/utils/feedback';
import { formatShortDate } from '../utils/format';

const attachmentIcon = (type: HomeworkAttachment['type']) => {
  switch (type) {
    case 'photo':
      return 'image-outline';
    case 'video':
      return 'videocam-outline';
    case 'link':
      return 'link-outline';
    case 'text':
      return 'text-outline';
    default:
      return 'document-outline';
  }
};

const AttachmentRow: React.FC<{ attachment: HomeworkAttachment }> = ({ attachment }) => {
  const { palette, spacing, radius, colors } = useTheme();

  if (attachment.type === 'text') {
    return (
      <View style={{ backgroundColor: palette.surfaceMuted, borderRadius: radius.md, padding: spacing.sm, marginTop: spacing.xs }}>
        {attachment.name ? (
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: 2 }}>{attachment.name}</Text>
        ) : null}
        <Text style={{ color: palette.textPrimary }}>{attachment.text}</Text>
      </View>
    );
  }

  const url = attachment.url ?? undefined;
  const onOpen = async () => {
    if (!url) return;
    if (attachment.type === 'link') {
      await Linking.openURL(url);
    } else {
      await Share.share({ message: url, url });
    }
  };

  return (
    <Pressable
      onPress={() => void onOpen()}
      disabled={!url}
      style={{
        flexDirection: 'row',
        alignItems: 'center',
        gap: spacing.sm,
        borderColor: palette.border,
        borderWidth: 1,
        borderRadius: radius.md,
        padding: spacing.sm,
        marginTop: spacing.xs,
      }}
    >
      <Soft3DIcon name={attachmentIcon(attachment.type)} tone="cyan" size={28} />
      <Text style={{ flex: 1, color: palette.textPrimary }} numberOfLines={1}>
        {attachment.name ?? attachment.url ?? attachment.type}
      </Text>
      <Ionicons
        name={attachment.type === 'link' ? 'open-outline' : 'share-outline'}
        size={16}
        color={colors.primary}
      />
    </Pressable>
  );
};

const HomeworkCard: React.FC<{ item: HomeworkAssignment; studentId: number }> = ({ item, studentId }) => {
  const { palette, spacing, typography, radius, colors } = useTheme();
  const statusQuery = useHomeworkDiaryStatus(item.id, studentId);
  const { complete, uncomplete } = useHomeworkCompletion(item.id);

  const isDone = statusQuery.data?.status === 'completed';
  const attachments = item.attachments ?? [];

  const onMarkDone = async () => {
    try {
      await complete.mutateAsync({ student_id: studentId });
    } catch (err) {
      showError('Could not update', err instanceof Error ? err.message : 'Try again.');
    }
  };
  const onUndo = async () => {
    try {
      await uncomplete.mutateAsync({ student_id: studentId });
    } catch (err) {
      showError('Could not update', err instanceof Error ? err.message : 'Try again.');
    }
  };

  return (
    <View
      style={{
        backgroundColor: palette.surface,
        borderColor: palette.border,
        borderWidth: 1,
        borderRadius: radius.lg,
        padding: spacing.md,
        marginBottom: spacing.sm,
      }}
    >
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm }}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>{item.title}</Text>
        {isDone ? (
          <StatusBadge label="Done" tone="success" />
        ) : item.status ? (
          <StatusBadge label={item.status} tone={item.status === 'active' ? 'brand' : 'warning'} />
        ) : null}
      </View>
      {item.subject_name ? (
        <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
          {item.subject_name}
          {item.teacher_name ? ` · ${item.teacher_name}` : ''}
        </Text>
      ) : null}
      {item.instructions || item.description ? (
        <Text style={{ color: palette.textPrimary, marginTop: spacing.sm }}>
          {item.instructions || item.description}
        </Text>
      ) : null}

      {attachments.map((att, index) => (
        <AttachmentRow key={index} attachment={att} />
      ))}

      <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
        Due {formatShortDate(item.due_date)}
        {item.max_score || item.total_marks ? ` · ${item.max_score ?? item.total_marks} marks` : ''}
      </Text>

      {isDone ? (
        <View style={{ marginTop: spacing.sm, flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          <Ionicons name="checkmark-circle" size={18} color={colors.success} />
          <Text style={{ color: colors.success, flex: 1, fontSize: typography.caption.fontSize }}>
            Marked done{statusQuery.data?.completed_at ? ` · ${formatShortDate(statusQuery.data.completed_at)}` : ''}
          </Text>
          <Button label="Undo" variant="ghost" onPress={() => void onUndo()} loading={uncomplete.isPending} fullWidth={false} />
        </View>
      ) : (
        <Button
          label="Mark as done"
          onPress={() => void onMarkDone()}
          loading={complete.isPending || statusQuery.isLoading}
          style={{ marginTop: spacing.sm }}
        />
      )}
    </View>
  );
};

export const ChildHomeworkScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildHomework'>>();
  const { spacing } = useTheme();
  const studentId = route.params.studentId;
  const homework = useChildHomework(studentId);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Homework"
        subtitle={homework.studentName ?? undefined}
        onBack={() => navigation.goBack()}
      />

      {homework.detailLoading || homework.isLoading ? (
        <SkeletonListRows count={4} />
      ) : homework.isError ? (
        <EmptyState
          title="Could not load homework"
          message={homework.error instanceof Error ? homework.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (homework.data ?? []).length === 0 ? (
        <EmptyState
          title="No assignments"
          message="Active homework for this child’s class will appear here."
          icon="book-outline"
        />
      ) : (
        (homework.data ?? []).map((item) => (
          <HomeworkCard key={item.id} item={item} studentId={studentId} />
        ))
      )}
    </ScreenContainer>
  );
};
