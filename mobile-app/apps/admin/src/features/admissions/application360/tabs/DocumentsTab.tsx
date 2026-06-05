import { API_BASE_URL, getToken, type ApplicationDetail } from '@erp/core';
import { ApplicationDocumentList } from '@erp/ui';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import React, { useCallback, useMemo, useState } from 'react';
import { Alert, ScrollView } from 'react-native';

export interface DocumentsTabProps {
  application: ApplicationDetail;
}

export const DocumentsTab: React.FC<DocumentsTabProps> = ({ application }) => {
  const [downloadingField, setDownloadingField] = useState<string | null>(null);

  const documents = useMemo(
    () =>
      application.documents.map((d) => ({
        field: d.field,
        label: d.label,
        uploaded: d.uploaded,
        viewUrl: d.view_url,
        isPrivate: d.is_private,
        downloadPath: d.download_path,
      })),
    [application.documents],
  );

  const handleDownloadPrivate = useCallback(
    async (doc: { field: string; downloadPath?: string | null; label: string }) => {
      if (!doc.downloadPath) return;
      setDownloadingField(doc.field);
      try {
        const token = await getToken();
        if (!token) {
          throw new Error('You are not signed in.');
        }
        const url = `${API_BASE_URL}${doc.downloadPath}`;
        const safeName = doc.field.replace(/[^a-z0-9_-]/gi, '_');
        const dest = `${FileSystem.cacheDirectory}${safeName}-${application.id}`;
        const result = await FileSystem.downloadAsync(url, dest, {
          headers: { Authorization: `Bearer ${token}` },
        });
        if (result.status !== 200) {
          throw new Error('Download failed.');
        }
        if (await Sharing.isAvailableAsync()) {
          await Sharing.shareAsync(result.uri);
        } else {
          Alert.alert('Downloaded', 'File saved to app cache.');
        }
      } catch (err) {
        Alert.alert('Download failed', (err as Error).message);
      } finally {
        setDownloadingField(null);
      }
    },
    [application.id],
  );

  return (
    <ScrollView showsVerticalScrollIndicator={false}>
      <ApplicationDocumentList
        documents={documents}
        onDownloadPrivate={handleDownloadPrivate}
        downloadingField={downloadingField}
      />
    </ScrollView>
  );
};
