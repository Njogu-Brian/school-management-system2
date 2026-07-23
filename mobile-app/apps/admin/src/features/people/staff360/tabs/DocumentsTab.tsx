import { documentsApi, downloadAuthenticatedFile, queryKeys, useStaffDocuments } from '@erp/core';
import { Button, EmptyState, TextField, useTheme } from '@erp/ui';
import { useQueryClient } from '@tanstack/react-query';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { showError, showSuccess } from '../../../shared/utils/feedback';

export interface DocumentsTabProps {
  staffId: number;
}

const DOC_TYPES = [
  { value: 'contract', label: 'Employment Contract' },
  { value: 'certificate', label: 'Certificate' },
  { value: 'id_copy', label: 'ID Copy' },
  { value: 'qualification', label: 'Qualification' },
  { value: 'other', label: 'Other' },
];

export const DocumentsTab: React.FC<DocumentsTabProps> = ({ staffId }) => {
  const { colors, palette, typography, spacing, radius } = useTheme();
  const query = useStaffDocuments(staffId);
  const queryClient = useQueryClient();
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [uploading, setUploading] = useState(false);
  const [showUpload, setShowUpload] = useState(false);
  const [title, setTitle] = useState('');
  const [documentType, setDocumentType] = useState('other');
  const [description, setDescription] = useState('');
  const [picked, setPicked] = useState<{
    uri: string;
    name: string;
    mimeType?: string | null;
  } | null>(null);

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

  const pickFile = async () => {
    try {
      const DocumentPicker = await import('expo-document-picker');
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        copyToCacheDirectory: true,
      });
      if (result.canceled || !result.assets?.[0]) return;
      const asset = result.assets[0];
      setPicked({
        uri: asset.uri,
        name: asset.name ?? 'document',
        mimeType: asset.mimeType,
      });
      if (!title.trim()) {
        setTitle(asset.name?.replace(/\.[^.]+$/, '') ?? 'Document');
      }
    } catch (err) {
      showError('Picker', err instanceof Error ? err.message : 'Could not open file picker.');
    }
  };

  const upload = async () => {
    if (!picked) {
      showError('Upload', 'Choose a file first.');
      return;
    }
    if (!title.trim()) {
      showError('Upload', 'Title is required.');
      return;
    }
    setUploading(true);
    try {
      const form = new FormData();
      form.append('title', title.trim());
      form.append('document_type', documentType);
      if (description.trim()) form.append('description', description.trim());
      form.append('file', {
        uri: picked.uri,
        name: picked.name,
        type: picked.mimeType ?? 'application/octet-stream',
      } as unknown as Blob);
      const res = await documentsApi.uploadStaffDocument(staffId, form);
      if (!res.success) throw new Error(res.message || 'Upload failed');
      showSuccess('Uploaded', 'Document saved.');
      setShowUpload(false);
      setTitle('');
      setDescription('');
      setPicked(null);
      await queryClient.invalidateQueries({ queryKey: queryKeys.documents.staff(staffId) });
    } catch (err) {
      showError('Upload failed', err instanceof Error ? err.message : 'Could not upload.');
    } finally {
      setUploading(false);
    }
  };

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

  return (
    <>
      <Button
        label={showUpload ? 'Hide upload' : 'Upload document'}
        variant="secondary"
        onPress={() => setShowUpload((v) => !v)}
        style={{ marginBottom: spacing.md }}
      />

      {showUpload ? (
        <View
          style={{
            borderWidth: 1,
            borderColor: palette.borderSubtle,
            borderRadius: radius.card,
            padding: spacing.md,
            marginBottom: spacing.md,
            backgroundColor: palette.surfaceRaised,
          }}
        >
          <TextField label="Title" value={title} onChangeText={setTitle} />
          <Text
            style={{
              color: palette.textMuted,
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
            }}
          >
            Document type
          </Text>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: spacing.sm }}>
            {DOC_TYPES.map((t) => (
              <Pressable
                key={t.value}
                onPress={() => setDocumentType(t.value)}
                style={{
                  paddingHorizontal: 10,
                  paddingVertical: 6,
                  borderRadius: radius.full,
                  borderWidth: 1,
                  borderColor: documentType === t.value ? colors.primary : palette.borderSubtle,
                  backgroundColor:
                    documentType === t.value ? palette.primaryMuted : palette.surfaceMuted,
                }}
              >
                <Text
                  style={{
                    color: documentType === t.value ? colors.primary : palette.textSecondary,
                    fontSize: typography.caption.fontSize,
                    fontWeight: '600',
                  }}
                >
                  {t.label}
                </Text>
              </Pressable>
            ))}
          </View>
          <TextField label="Description (optional)" value={description} onChangeText={setDescription} />
          <Button
            label={picked ? `File: ${picked.name}` : 'Choose file'}
            variant="ghost"
            onPress={() => void pickFile()}
          />
          <Button
            label="Upload"
            onPress={() => void upload()}
            loading={uploading}
            style={{ marginTop: spacing.sm }}
          />
        </View>
      ) : null}

      {docs.length === 0 ? (
        <EmptyState
          title="No documents"
          message="Upload contracts, certificates, ID copies, and other HR files here."
          icon="folder-open-outline"
        />
      ) : (
        docs.map((doc) => (
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
                <Text
                  style={{
                    color: colors.primary,
                    fontWeight: '600',
                    fontSize: typography.caption.fontSize,
                  }}
                >
                  {downloadingId === doc.id ? 'Downloading…' : 'View / download'}
                </Text>
              </Pressable>
            ) : null}
          </View>
        ))
      )}
    </>
  );
};
