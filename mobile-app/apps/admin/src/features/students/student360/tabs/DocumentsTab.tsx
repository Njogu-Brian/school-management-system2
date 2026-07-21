import { downloadAuthenticatedFile, useStudentDocuments } from '@erp/core';
import { EmptyState, useTheme } from '@erp/ui';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { showError } from '../../../shared/utils/feedback';

export interface DocumentsTabProps {
  studentId: number;
}

export const DocumentsTab: React.FC<DocumentsTabProps> = ({ studentId }) => {
  const { colors, palette, typography, spacing, radius } = useTheme();
  const query = useStudentDocuments(studentId);
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
        message="No documents are attached to this student profile yet."
        icon="document-text-outline"
      />
    );
  }

  return (
    <>
      {docs.map((doc) => (
        <View
          key={doc.id}
          style={{
            borderWidth: StyleSheet.hairlineWidth,
            borderColor: palette.border,
            borderRadius: radius.md,
            padding: spacing.sm,
            marginBottom: spacing.xs,
          }}
        >
          <Text style={{ color: palette.textMain, fontWeight: '600' }}>{doc.title}</Text>
          <Text
            style={{
              color: palette.textSub,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
            }}
          >
            {[doc.document_type, doc.category, doc.file_name].filter(Boolean).join(' · ') || '—'}
          </Text>
          {doc.download_path ? (
            <Pressable onPress={() => void handleDownload(doc)} style={{ marginTop: spacing.sm }}>
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
