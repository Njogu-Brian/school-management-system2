import { downloadAuthenticatedFile, useStaffDocuments } from '@erp/core';
import { EmptyState } from '@erp/ui';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface DocumentsTabProps {
  staffId: number;
}

export const DocumentsTab: React.FC<DocumentsTabProps> = ({ staffId }) => {
  const { colors, palette, fontSizes, spacing } = useTheme();
  const query = useStaffDocuments(staffId);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  const handleDownload = useCallback(
    async (doc: { id: number; title: string; download_path?: string | null }) => {
      if (!doc.download_path) return;
      setDownloadingId(doc.id);
      try {
        await downloadAuthenticatedFile(doc.download_path, doc.title);
      } catch (err) {
        Alert.alert('Download failed', (err as Error).message);
      } finally {
        setDownloadingId(null);
      }
    },
    [],
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

  const docs = query.data ?? [];
  if (docs.length === 0) {
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
      {docs.map((doc) => (
        <View
          key={doc.id}
          style={{
            borderWidth: 1,
            borderColor: palette.border,
            borderRadius: 8,
            padding: spacing.sm,
            marginBottom: spacing.xs,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{doc.title}</Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
            {[doc.document_type, doc.expiry_date].filter(Boolean).join(' · ') || '—'}
          </Text>
          {doc.download_path ? (
            <Pressable onPress={() => void handleDownload(doc)} style={{ marginTop: 8 }}>
              <Text style={{ color: colors.primary, fontWeight: '600' }}>
                {downloadingId === doc.id ? 'Downloading…' : 'Download'}
              </Text>
            </Pressable>
          ) : null}
        </View>
      ))}
    </>
  );
};
