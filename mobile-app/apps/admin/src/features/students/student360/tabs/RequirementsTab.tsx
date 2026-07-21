import { useStudentRequirements } from '@erp/core';
import { EmptyState, FinanceFieldSection, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, View } from 'react-native';

export interface RequirementsTabProps {
  studentId: number;
}

/** Term requirements from `GET /teacher/requirements/students/{id}/templates`. */
export const RequirementsTab: React.FC<RequirementsTabProps> = ({ studentId }) => {
  const { colors, spacing } = useTheme();
  const query = useStudentRequirements(studentId);

  const rows = useMemo(() => {
    const items = query.data?.items ?? [];
    return items.map((item) => ({
      label: item.name,
      value: `${item.quantity_collected}/${item.quantity_required} ${item.unit ?? ''} · ${item.status}`.trim(),
    }));
  }, [query.data]);

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Could not load requirements"
        message={(query.error as Error).message}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }

  if (rows.length === 0) {
    return (
      <EmptyState
        title="No requirements"
        message="No term requirement templates are assigned to this student."
        icon="clipboard-outline"
      />
    );
  }

  return <FinanceFieldSection title="Requirements checklist" rows={rows} />;
};
