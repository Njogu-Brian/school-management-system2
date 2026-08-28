import { useInfiniteStudentList } from '@erp/core';
import {
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import React, { useMemo } from 'react';
import { FlatList, Text, View } from 'react-native';
import { UsersAppHeaderChrome } from '../../../navigation/UsersAppHeaderChrome';
import { ChildAcademicProgressSection } from '../components/ChildAcademicProgressSection';

/**
 * Academic tab root — chrome is rendered in-screen (not as a stack header) so
 * content never sits under the phone status bar / notch.
 */
export const ParentAcademicScreen: React.FC = () => {
  const { spacing, palette, typography } = useTheme();
  const listQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 40,
  });
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <View style={{ flex: 1, backgroundColor: palette.background }}>
      <UsersAppHeaderChrome title="Academic" />
      <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
        {listQuery.isLoading ? (
          <SkeletonListRows count={3} />
        ) : students.length === 0 ? (
          <EmptyState
            title="No children linked"
            message="Link children to view academic progress graphs."
            icon="school-outline"
          />
        ) : (
          <FlatList
            data={students}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
            ListHeaderComponent={
              <Text
                style={{
                  marginBottom: spacing.sm,
                  fontWeight: '600',
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                }}
              >
                Progress trends · overall and by subject
              </Text>
            }
            renderItem={({ item }) => (
              <ChildAcademicProgressSection
                studentId={item.id}
                name={item.fullName}
                meta={[item.admissionNumber, item.className].filter(Boolean).join(' · ')}
                showQuickLinks
              />
            )}
          />
        )}
      </ScreenContainer>
    </View>
  );
};
