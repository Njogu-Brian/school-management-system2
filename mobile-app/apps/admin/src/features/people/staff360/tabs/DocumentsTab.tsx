import { downloadAuthenticatedFile, useStaffDocuments } from '@erp/core';
import { EmptyState, useTheme } from '@erp/ui';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { showError } from '../../../shared/utils/feedback';

export interface DocumentsTabProps {
  staffId: number;
}

export const DocumentsTab: React.FC<DocumentsTabProps> = ({ staffId }) => {
  const { colors, palette, typography, spacing, radius } = useTheme();
  const query = useStaffDocuments(staffId);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  const handleDownload = useCallback(
    async (doc: { id: number; title: string; download_path?: string | null }) => {
      if (!doc.download_path) return;
      setDownloadingId(doc.id);
      try {
        await downloadAuthenticatedFile(doc.download_path, doc.title);
      } catch (err) {
        showError('Download failed', (err as Error).message);
      } finally {
        setDownloadingId(null);
      }
    },
    [],
  );

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
        title="Could not load documents"
        message={(query.error as Error).message}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
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
      {docs.map((doc) => (
        <View
          key={doc.id}
          style={{
            borderWidth: 1,
            borderColor: palette.borderSubtle,
            borderRadius: radius.card,
            padding: spacing.md,
            marginBottom: spacing.sm,
            backgroundColor: palette.surfaceRaised,
          }}
        >
          <Text
            style={{
              color: palette.textPrimary,
              fontWeight: '600',
              fontSize: typography.body.fontSize,
            }}
          >
            {doc.title}
          </Text>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              marginTop: 4,
            }}
          >
            {[doc.document_type, doc.expiry_date].filter(Boolean).join(' · ') || '—'}
          </Text>
          {doc.download_path ? (
            <Pressable onPress={() => void handleDownload(doc)} style={{ marginTop: 8 }}>
              <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
                {downloadingId === doc.id ? 'Downloading…' : 'Download'}
              </Text>
            </Pressable>
          ) : null}
        </View>
      ))}
    </>
  );
};
