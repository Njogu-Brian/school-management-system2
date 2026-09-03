import {
  documentsApi,
  downloadAuthenticatedFile,
  emptyKemisParentSlotValues,
  kemisParentSlotFromApi,
  kemisParentSlotPayload,
  useKemisOptions,
  useParentProfileReview,
  useUpdateParentProfileReview,
  type KemisParentSlotValues,
} from '@erp/core';
import { Button, KemisParentIdentityFields, TextField, useTheme } from '@erp/ui';
import React, { useEffect, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

export const ParentAccountProfileSection: React.FC = () => {
  const { palette, spacing, typography, radius, colors, elevation } = useTheme();
  const query = useParentProfileReview();
  const save = useUpdateParentProfileReview();
  const kemisOptionsQuery = useKemisOptions();
  const [father, setFather] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [mother, setMother] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [guardian, setGuardian] = useState<KemisParentSlotValues>(emptyKemisParentSlotValues());
  const [guardianRelationship, setGuardianRelationship] = useState('');
  const [residential, setResidential] = useState('');
  const [uploading, setUploading] = useState(false);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  useEffect(() => {
    const p = query.data?.parent as Record<string, unknown> | undefined;
    if (!p) return;
    setFather(kemisParentSlotFromApi(p, 'father'));
    setMother(kemisParentSlotFromApi(p, 'mother'));
    setGuardian(kemisParentSlotFromApi(p, 'guardian'));
    setGuardianRelationship(String(p.guardian_relationship ?? ''));
    const firstStudent = query.data?.students[0];
    setResidential(firstStudent?.residential_area ?? '');
  }, [query.data]);

  const kemisOptions = kemisOptionsQuery.data;
  const groupStyle = [
    elevation?.[1] ?? {},
    {
      borderRadius: radius.lg,
      backgroundColor: palette.surface,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: palette.border,
      padding: spacing.md,
      marginBottom: spacing.sm,
    },
  ];

  const onSave = async () => {
    try {
      await save.mutateAsync({
        residential_area: residential.trim() || null,
        guardian_relationship: guardianRelationship.trim() || null,
        ...kemisParentSlotPayload('father', father),
        ...kemisParentSlotPayload('mother', mother),
        ...kemisParentSlotPayload('guardian', guardian),
      });
      showSuccess('Saved', 'Your family contact details were updated.');
    } catch (err) {
      showError('Could not save', err instanceof Error ? err.message : 'Try again.');
    }
  };

  const onUploadId = async (slot: 'father' | 'mother' | 'guardian') => {
    try {
      const DocumentPicker = await import('expo-document-picker');
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/*'],
        copyToCacheDirectory: true,
      });
      if (result.canceled || !result.assets?.[0]) return;
      const asset = result.assets[0];
      setUploading(true);
      const res = await documentsApi.uploadParentIdCard(
        {
          uri: asset.uri,
          name: asset.name ?? 'id-card',
          type: asset.mimeType ?? 'application/octet-stream',
        },
        slot,
      );
      if (!res.success) throw new Error(res.message || 'Upload failed');
      showSuccess('Uploaded', 'ID document saved.');
      void query.refetch();
    } catch (err) {
      showError('Upload failed', (err as Error).message);
    } finally {
      setUploading(false);
    }
  };

  const docs = query.data?.documents ?? [];

  return (
    <>
      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.overline?.fontSize ?? 11,
          fontWeight: '700',
          letterSpacing: 0.6,
          textTransform: 'uppercase',
          marginBottom: spacing.sm,
          marginTop: spacing.md,
        }}
      >
        Family contacts
      </Text>
      <View style={groupStyle}>
        <TextField label="Home / contact address" value={residential} onChangeText={setResidential} />
        {kemisOptions ? (
          <>
            <KemisParentIdentityFields slot="father" title="Father" values={father} onChange={setFather} options={kemisOptions} />
            <KemisParentIdentityFields slot="mother" title="Mother" values={mother} onChange={setMother} options={kemisOptions} />
            <KemisParentIdentityFields
              slot="guardian"
              title="Guardian"
              values={guardian}
              onChange={setGuardian}
              options={kemisOptions}
              showRelationship
              relationship={guardianRelationship}
              onRelationshipChange={setGuardianRelationship}
            />
          </>
        ) : (
          <Text style={{ color: palette.textMuted }}>Loading contact fields…</Text>
        )}
        <Button
          label={save.isPending ? 'Saving…' : 'Save my details'}
          onPress={() => void onSave()}
          loading={save.isPending}
          style={{ marginTop: spacing.sm }}
        />
      </View>

      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.overline?.fontSize ?? 11,
          fontWeight: '700',
          letterSpacing: 0.6,
          textTransform: 'uppercase',
          marginBottom: spacing.sm,
          marginTop: spacing.md,
        }}
      >
        Parent documents
      </Text>
      <View style={groupStyle}>
        <Button label="Upload father ID" variant="secondary" onPress={() => void onUploadId('father')} loading={uploading} />
        <Button
          label="Upload mother ID"
          variant="secondary"
          onPress={() => void onUploadId('mother')}
          loading={uploading}
          style={{ marginTop: spacing.sm }}
        />
        <Button
          label="Upload guardian ID"
          variant="secondary"
          onPress={() => void onUploadId('guardian')}
          loading={uploading}
          style={{ marginTop: spacing.sm }}
        />
        {docs.length === 0 ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.sm }}>No parent documents yet.</Text>
        ) : (
          docs.map((doc) => (
            <View key={doc.id} style={{ marginTop: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{doc.title ?? doc.file_name}</Text>
              {doc.download_path ? (
                <Pressable
                  onPress={() => {
                    void (async () => {
                      setDownloadingId(doc.id);
                      try {
                        await downloadAuthenticatedFile(doc.download_path, doc.title ?? 'document');
                      } catch (err) {
                        showError('Download failed', (err as Error).message);
                      } finally {
                        setDownloadingId(null);
                      }
                    })();
                  }}
                >
                  <Text style={{ color: colors.primary, fontWeight: '600', marginTop: 4 }}>
                    {downloadingId === doc.id ? 'Opening…' : 'View / download'}
                  </Text>
                </Pressable>
              ) : null}
            </View>
          ))
        )}
      </View>
    </>
  );
};
