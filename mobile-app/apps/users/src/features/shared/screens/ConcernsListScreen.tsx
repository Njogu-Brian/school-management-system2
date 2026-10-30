import { useConcernsList } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { Pressable, Text, View } from 'react-native';
import { formatDateTime } from '../utils/format';

/** Shared across every role — server scopes staff to concerns they are tagged on. */
export const ConcernsListScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography } = useTheme();
  const list = useConcernsList();
  const nav = navigation as { navigate: (name: string, params?: object) => void };

  return (
    <ScreenContainer
      scroll
      edges={['top', 'bottom']}
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
    >
      <AcademicScreenHeader
        title="Concerns"
        subtitle="Tagged to you (or raised by you)"
        onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
      />

      <Button
        label="Raise new concern"
        onPress={() => nav.navigate('RaiseConcern', { studentId: undefined })}
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
          <Pressable key={item.id} onPress={() => nav.navigate('ConcernDetail', { concernId: item.id })}>
            <SurfaceCard accent={item.status === 'open' ? 'warning' : 'info'}>
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
              {(item.raised_by_label || item.raised_by?.label) ? (
                <Text style={{ color: palette.textMuted, marginTop: 2, fontSize: typography.caption.fontSize }}>
                  Raised by {item.raised_by_label ?? item.raised_by?.label}
                </Text>
              ) : null}
              <Text style={{ color: palette.textPrimary, marginTop: spacing.sm }} numberOfLines={4}>
                {item.description}
              </Text>
              <Text style={{ color: palette.textMuted, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                {formatDateTime(item.created_at)}
              </Text>
            </SurfaceCard>
          </Pressable>
        ))
      )}
    </ScreenContainer>
  );
};
