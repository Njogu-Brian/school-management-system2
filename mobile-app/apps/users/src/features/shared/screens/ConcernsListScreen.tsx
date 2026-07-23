import { useConcernsList } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';
import { formatDateTime } from '../utils/format';

/** Shared across every role (Teacher, Parent, Driver, Student) — reuses `/concerns` server scoping. */
export const ConcernsListScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography, radius } = useTheme();
  const list = useConcernsList();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Concerns"
        onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
      />

      <Button
        label="Raise new concern"
        onPress={() => (navigation as { navigate: (name: string, params?: object) => void }).navigate('RaiseConcern', { studentId: undefined })}
        style={{ marginBottom: spacing.md }}
      />

      {list.isLoading ? (
        <SkeletonListRows count={4} />
      ) : list.isError ? (
        <EmptyState
          title="Could not load concerns"
          message={list.error instanceof Error ? list.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (list.data ?? []).length === 0 ? (
        <EmptyState
          title="No concerns yet"
          message="Submitted concerns will appear here."
          icon="alert-circle-outline"
        />
      ) : (
        (list.data ?? []).map((item) => (
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
              <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1, textTransform: 'capitalize' }}>
                {item.category}
              </Text>
              <StatusBadge label={item.status} tone="info" />
            </View>
            <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
              {item.student_name ?? `Student #${item.student_id}`}
              {item.class_name ? ` · ${item.class_name}` : ''}
            </Text>
            <Text style={{ color: palette.textPrimary, marginTop: spacing.sm }} numberOfLines={4}>
              {item.description}
            </Text>
            <Text style={{ color: palette.textMuted, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
              {formatDateTime(item.created_at)}
            </Text>
          </View>
        ))
      )}
    </ScreenContainer>
  );
};
