import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { useChildHomework } from '../hooks/useChildHomework';
import { formatShortDate } from '../utils/format';

export const ChildHomeworkScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildHomework'>>();
  const { palette, spacing, typography, radius } = useTheme();
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
          <View
            key={item.id}
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
              {item.status ? (
                <StatusBadge
                  label={item.status}
                  tone={item.status === 'active' ? 'success' : 'info'}
                />
              ) : null}
            </View>
            {item.subject_name ? (
              <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
                {item.subject_name}
                {item.teacher_name ? ` · ${item.teacher_name}` : ''}
              </Text>
            ) : null}
            {item.description ? (
              <Text style={{ color: palette.textPrimary, marginTop: spacing.sm }} numberOfLines={3}>
                {item.description}
              </Text>
            ) : null}
            <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
              Due {formatShortDate(item.due_date)}
              {item.total_marks ? ` · ${item.total_marks} marks` : ''}
            </Text>
          </View>
        ))
      )}
    </ScreenContainer>
  );
};
