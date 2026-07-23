import {
  useCurrentUser,
  useHomeworkDetail,
  useHomeworkDiaryRoster,
  useHomeworkList,
  UserRole,
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
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { FlatList, Linking, Pressable, Share, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const AssignmentsHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();
  const listQuery = useHomeworkList();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="Homework" subtitle="Assignments for your classes" onBack={() => navigation.goBack()} />
        <Button
          label="Create homework"
          onPress={() => navigation.navigate('CreateAssignment')}
          style={{ marginBottom: spacing.sm }}
        />
      </View>
      {listQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : (listQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No homework yet"
          message="Create an assignment so parents and students can see it."
          icon="document-text-outline"
          actionLabel="Create"
          onAction={() => navigation.navigate('CreateAssignment')}
        />
      ) : (
        <FlatList
          data={listQuery.data ?? []}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('AssignmentDetail', { assignmentId: item.id })}
              style={{
                flexDirection: 'row',
                gap: spacing.md,
                alignItems: 'center',
                backgroundColor: palette.surface,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Soft3DIcon name="document-text-outline" tone="amber" size={40} />
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {[item.subject_name, item.class_name, item.due_date].filter(Boolean).join(' · ')}
                </Text>
              </View>
            </Pressable>
          )}
        />
      )}
    </ScreenContainer>
  );
};

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
  const { palette, spacing, typography, radius, colors } = useTheme();

  if (attachment.type === 'text') {
    return (
      <View
        style={{
          backgroundColor: palette.surfaceMuted,
          borderRadius: radius.md,
          padding: spacing.sm,
          marginTop: spacing.sm,
        }}
      >
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
        backgroundColor: palette.surface,
        borderColor: palette.border,
        borderWidth: 1,
        borderRadius: radius.md,
        padding: spacing.sm,
        marginTop: spacing.sm,
      }}
    >
      <Soft3DIcon name={attachmentIcon(attachment.type)} tone="indigo" size={32} />
      <View style={{ flex: 1 }}>
        <Text style={{ color: palette.textPrimary, fontWeight: '600' }} numberOfLines={1}>
          {attachment.name ?? attachment.url ?? attachment.type}
        </Text>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
          {attachment.type === 'link' ? 'Open link' : url ? 'Tap to open / share' : 'No file URL'}
        </Text>
      </View>
      <Ionicons
        name={attachment.type === 'link' ? 'open-outline' : 'share-outline'}
        size={18}
        color={colors.primary}
      />
    </Pressable>
  );
};

export const AssignmentDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<TeacherStackParamList, 'AssignmentDetail'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const assignmentId = route.params.assignmentId;
  const detail = useHomeworkDetail(assignmentId);
  const user = useCurrentUser();
  const isTeacher =
    user?.role === UserRole.TEACHER ||
    user?.role === UserRole.SENIOR_TEACHER ||
    user?.role === UserRole.SUPERVISOR;
  const roster = useHomeworkDiaryRoster(assignmentId, { enabled: isTeacher });

  const attachments = detail.data?.attachments ?? [];

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Homework detail" onBack={() => navigation.goBack()} />
      {detail.isLoading ? (
        <SkeletonListRows count={4} />
      ) : detail.isError || !detail.data ? (
        <EmptyState
          title="Could not load"
          message={detail.error instanceof Error ? detail.error.message : 'Try again.'}
          icon="alert-circle-outline"
        />
      ) : (
        <>
          <View
            style={{
              backgroundColor: palette.surface,
              borderWidth: 1,
              borderColor: palette.border,
              borderRadius: radius.lg,
              padding: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.headline.fontSize }}>
              {detail.data.title}
            </Text>
            <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>
              {[detail.data.subject_name, detail.data.class_name, detail.data.stream_name, detail.data.due_date]
                .filter(Boolean)
                .join(' · ')}
            </Text>
            {detail.data.max_score ? (
              <Text style={{ color: palette.textMuted, marginTop: 4, fontSize: typography.caption.fontSize }}>
                {detail.data.max_score} marks
              </Text>
            ) : null}
            <Text style={{ color: palette.textPrimary, marginTop: spacing.md }}>
              {detail.data.instructions || detail.data.description || 'No instructions provided.'}
            </Text>
          </View>

          {attachments.length > 0 ? (
            <View style={{ marginTop: spacing.lg }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
                Attachments
              </Text>
              {attachments.map((att, index) => (
                <AttachmentRow key={index} attachment={att} />
              ))}
            </View>
          ) : null}

          {isTeacher ? (
            <View style={{ marginTop: spacing.lg }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Completion roster</Text>
                {roster.data ? (
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                    {roster.data.completed}/{roster.data.total} done
                  </Text>
                ) : null}
              </View>
              {roster.isLoading ? (
                <SkeletonListRows count={3} />
              ) : roster.isError ? (
                <EmptyState
                  title="Could not load roster"
                  message={roster.error instanceof Error ? roster.error.message : 'Try again.'}
                  icon="alert-circle-outline"
                />
              ) : (roster.data?.students ?? []).length === 0 ? (
                <EmptyState title="No students" message="No students found for this class." icon="people-outline" />
              ) : (
                (roster.data?.students ?? []).map((row) => (
                  <View
                    key={row.student_id}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      gap: spacing.sm,
                      backgroundColor: palette.surface,
                      borderColor: palette.border,
                      borderWidth: 1,
                      borderRadius: radius.md,
                      padding: spacing.sm,
                      marginTop: spacing.sm,
                    }}
                  >
                    <View style={{ flex: 1 }}>
                      <Text style={{ color: palette.textPrimary, fontWeight: '600' }} numberOfLines={1}>
                        {row.student_name ?? `Student #${row.student_id}`}
                      </Text>
                      {row.admission_number ? (
                        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                          {row.admission_number}
                        </Text>
                      ) : null}
                    </View>
                    <StatusBadge
                      label={row.status === 'completed' ? 'Done' : row.status}
                      tone={row.status === 'completed' ? 'success' : 'warning'}
                    />
                  </View>
                ))
              )}
            </View>
          ) : null}
        </>
      )}
    </ScreenContainer>
  );
};
