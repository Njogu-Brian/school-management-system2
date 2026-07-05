import { useStudentRequirements } from '@erp/core';
import { EmptyState, FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface RequirementsTabProps {
  studentId: number;
}

/** Term requirements from `GET /teacher/requirements/students/{id}/templates`. */
export const RequirementsTab: React.FC<RequirementsTabProps> = ({ studentId }) => {
  const { colors } = useTheme();
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
      <View style={{ paddingVertical: 24, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <View style={{ alignItems: 'center', paddingVertical: 16 }}>
        <Text style={{ color: colors.error }}>{(query.error as Error).message}</Text>
        <Pressable onPress={() => void query.refetch()} style={{ marginTop: 8 }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </View>
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

  return (
    <>
      <FinanceFieldSection title="Requirements checklist" rows={rows} />
    </>
  );
};
