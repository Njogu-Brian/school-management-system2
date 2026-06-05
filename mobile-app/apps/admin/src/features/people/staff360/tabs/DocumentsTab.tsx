import { useStaffDocuments } from '@erp/core';
import { EmptyState, FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface DocumentsTabProps {
  staffId: number;
}

/** `GET /staff/{id}/documents` */
export const DocumentsTab: React.FC<DocumentsTabProps> = ({ staffId }) => {
  const { colors, palette, fontSizes } = useTheme();
  const query = useStaffDocuments(staffId);

  const rows = useMemo(
    () =>
      (query.data ?? []).map((doc) => ({
        label: doc.title,
        value: [doc.document_type, doc.expiry_date].filter(Boolean).join(' · ') || '—',
      })),
    [query.data],
  );

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
        title="No documents"
        message="No HR documents are uploaded for this staff member yet."
        icon="folder-open-outline"
      />
    );
  }

  return (
    <>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 8 }}>
        API: GET /staff/{'{id}'}/documents
      </Text>
      <FinanceFieldSection title="HR documents" rows={rows} />
    </>
  );
};
