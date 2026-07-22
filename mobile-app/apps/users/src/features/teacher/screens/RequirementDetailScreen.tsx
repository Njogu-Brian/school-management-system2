import { useStudentRequirements } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Route = RouteProp<TeacherStackParamList, 'RequirementDetail'>;

export const RequirementDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { studentId } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const detailQuery = useStudentRequirements(studentId);

  const student = detailQuery.data?.student;
  const items = detailQuery.data?.items ?? [];

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        refreshControl={
          <RefreshControl
            refreshing={detailQuery.isRefetching}
            onRefresh={() => void detailQuery.refetch()}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader
          title={student?.full_name ?? 'Requirements'}
          subtitle={
            [student?.admission_number, student?.class_name, detailQuery.data?.current_term?.name]
              .filter(Boolean)
              .join(' · ') || `Student #${studentId}`
          }
          onBack={() => navigation.goBack()}
        />

        {detailQuery.isLoading && !detailQuery.data ? (
          <SkeletonListRows variant="compact" count={4} />
        ) : detailQuery.isError ? (
          <EmptyState
            title="Could not load requirements"
            message={(detailQuery.error as Error)?.message ?? 'Something went wrong.'}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void detailQuery.refetch()}
          />
        ) : items.length === 0 ? (
          <EmptyState
            title="No templates"
            message="No requirement templates for this student."
            icon="clipboard-outline"
          />
        ) : (
          items.map((item) => (
            <View
              key={item.template_id}
              style={[
                styles.card,
                {
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.name}</Text>
              {item.notes ? (
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  {item.notes}
                </Text>
              ) : null}
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 6 }}>
                {`${item.status} · ${item.quantity_collected}/${item.quantity_required}${item.unit ? ` ${item.unit}` : ''}`}
              </Text>
            </View>
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
});
