import { admissionsApi, API_BASE_URL, getToken, type ApplicationDetail } from '@erp/core';
import { ApplicationDocumentList } from '@erp/ui';
import * as FileSystem from 'expo-file-system/legacy';
import * as ImagePicker from 'expo-image-picker';
import * as Sharing from 'expo-sharing';
import React, { useCallback, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, Text, View } from 'react-native';
import { showError, showSuccess } from '../../../shared/utils/feedback';

export interface DocumentsTabProps {
  application: ApplicationDetail;
}

export const DocumentsTab: React.FC<DocumentsTabProps> = ({ application }) => {
  const [downloadingField, setDownloadingField] = useState<string | null>(null);
  const [uploadingField, setUploadingField] = useState<string | null>(null);

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
          showSuccess('Downloaded', 'File saved to app cache.');
        }
      } catch (err) {
        showError('Download failed', (err as Error).message);
      } finally {
        setDownloadingField(null);
      }
    },
    [application.id],
  );

  const captureAndUpload = useCallback(
    async (field: string, label: string) => {
      setUploadingField(field);
      try {
        const permission = await ImagePicker.requestCameraPermissionsAsync();
        if (!permission.granted) {
          throw new Error('Camera permission is required to capture documents.');
        }
        const picked = await ImagePicker.launchCameraAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          quality: 0.8,
        });
        if (picked.canceled || !picked.assets?.[0]) {
          return;
        }
        const asset = picked.assets[0];
        const name = `${field}-${Date.now()}.jpg`;
        const res = await admissionsApi.uploadDocument(application.id, field, {
          uri: asset.uri,
          name,
          type: asset.mimeType ?? 'image/jpeg',
        });
        if (!res.success) {
          throw new Error(res.message || 'Upload failed');
        }
        showSuccess('Uploaded', `${label} saved.`);
      } catch (err) {
        showError('Upload failed', (err as Error).message);
      } finally {
        setUploadingField(null);
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
      {!application.enrolled && (
        <View style={{ padding: 16, gap: 10 }}>
          <Text style={{ fontWeight: '700', marginBottom: 4 }}>Capture with camera</Text>
          {documents.map((doc) => (
            <Pressable
              key={doc.field}
              onPress={() => void captureAndUpload(doc.field, doc.label)}
              style={{
                borderWidth: 1,
                borderColor: '#ddd',
                borderRadius: 10,
                padding: 12,
                flexDirection: 'row',
                justifyContent: 'space-between',
                alignItems: 'center',
              }}
            >
              <Text>
                {doc.label}
                {doc.uploaded ? ' · uploaded' : ''}
              </Text>
              {uploadingField === doc.field ? (
                <ActivityIndicator />
              ) : (
                <Text style={{ color: '#5B2C8E', fontWeight: '600' }}>Camera</Text>
              )}
            </Pressable>
          ))}
        </View>
      )}
    </ScrollView>
  );
};
