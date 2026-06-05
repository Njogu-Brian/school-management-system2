import { useAnnouncement, useCan, useCreateAnnouncement, useUpdateAnnouncement } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'AnnouncementForm'>;

export const AnnouncementFormScreen: React.FC<Props> = ({ navigation, route }) => {
  const editId = route.params?.announcementId;
  const isEdit = editId != null && editId > 0;
  const canEdit = useCan('communication.view');
  const { palette, spacing, fontSizes } = useTheme();
  const detailQuery = useAnnouncement(editId ?? 0, { enabled: isEdit && canEdit });
  const createMutation = useCreateAnnouncement();
  const updateMutation = useUpdateAnnouncement();

  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [expiresAt, setExpiresAt] = useState('');
  const [active, setActive] = useState(true);

  useEffect(() => {
    if (detailQuery.data) {
      setTitle(detailQuery.data.title);
      setContent(detailQuery.data.content);
      setExpiresAt(detailQuery.data.expires_at ?? '');
      setActive(detailQuery.data.active ?? true);
    }
  }, [detailQuery.data]);

  if (!canEdit) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (isEdit && detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator />
      </ScreenContainer>
    );
  }

  const canSubmit = title.trim().length > 0 && content.trim().length > 0;
  const pending = createMutation.isPending || updateMutation.isPending;

  const onSave = async (publish: boolean) => {
    if (!canSubmit) {
      showError('Validation', 'Title and content are required.');
      return;
    }
    const payload = {
      title: title.trim(),
      content: content.trim(),
      active: publish,
      expires_at: expiresAt.trim() || null,
    };
    try {
      if (isEdit && editId) {
        await updateMutation.mutateAsync({ id: editId, ...payload });
        showSuccess('Updated', 'Announcement saved.', () => navigation.goBack());
      } else {
        await createMutation.mutateAsync(payload);
        showSuccess('Created', publish ? 'Announcement published.' : 'Draft saved.', () => navigation.goBack());
      }
    } catch (err) {
      showError('Save failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title={isEdit ? 'Edit announcement' : 'New announcement'}
        onBack={() => navigation.goBack()}
      />

      <TextField label="Title" value={title} onChangeText={setTitle} placeholder="Announcement title" />
      <TextField
        label="Content"
        value={content}
        onChangeText={setContent}
        placeholder="Message body"
        multiline
        numberOfLines={6}
        textAlignVertical="top"
      />
      <TextField
        label="Expiry date (YYYY-MM-DD)"
        value={expiresAt}
        onChangeText={setExpiresAt}
        placeholder="Optional"
      />

      <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm }}>
        <Pressable onPress={() => setActive(true)} style={[styles.chip, active && styles.chipActive]}>
          <Text style={{ fontSize: fontSizes.sm }}>Publish now</Text>
        </Pressable>
        <Pressable onPress={() => setActive(false)} style={[styles.chip, !active && styles.chipActive]}>
          <Text style={{ fontSize: fontSizes.sm }}>Save as draft</Text>
        </Pressable>
      </View>

      <View style={{ marginTop: spacing.lg, gap: spacing.sm }}>
        <Button
          label={active ? 'Save & publish' : 'Save draft'}
          onPress={() => void onSave(active)}
          disabled={!canSubmit || pending}
          loading={pending}
        />
        {isEdit ? (
          <Button label="Preview" variant="ghost" onPress={() => navigation.navigate('AnnouncementDetail', { announcementId: editId! })} />
        ) : null}
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 20, paddingHorizontal: 14, paddingVertical: 8 },
  chipActive: { borderColor: '#004A99', backgroundColor: '#E8F0FA' },
});
