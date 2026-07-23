import {
  useClassroomSubjects,
  useCreateHomework,
  useSettingsClasses,
  type HomeworkFileInput,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  Soft3DIcon,
  TextField,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import React, { useEffect, useState } from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

type LocalFile = HomeworkFileInput & { kind: 'photo' | 'video' | 'document' };
type LocalLink = { url: string; label: string };

export const CreateAssignmentScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography, colors, radius } = useTheme();
  const classesQuery = useSettingsClasses();
  const createMutation = useCreateHomework();

  const [title, setTitle] = useState('');
  const [instructions, setInstructions] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [maxScore, setMaxScore] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const subjectsQuery = useClassroomSubjects(classroomId);

  const [files, setFiles] = useState<LocalFile[]>([]);
  const [links, setLinks] = useState<LocalLink[]>([]);
  const [linkUrl, setLinkUrl] = useState('');
  const [linkLabel, setLinkLabel] = useState('');

  useEffect(() => {
    setSubjectId(null);
  }, [classroomId]);

  const attachPhoto = async (media: 'photo' | 'video') => {
    try {
      const ImagePicker = await import('expo-image-picker');
      const picked = await ImagePicker.launchImageLibraryAsync({
        mediaTypes:
          media === 'video'
            ? ImagePicker.MediaTypeOptions.Videos
            : ImagePicker.MediaTypeOptions.Images,
        quality: 0.85,
      });
      if (picked.canceled || !picked.assets?.[0]?.uri) return;
      const asset = picked.assets[0];
      const fallbackName = media === 'video' ? 'video.mp4' : 'photo.jpg';
      const fallbackType = media === 'video' ? 'video/mp4' : 'image/jpeg';
      setFiles((prev) => [
        ...prev,
        {
          kind: media,
          uri: asset.uri,
          name: asset.fileName ?? fallbackName,
          type: asset.mimeType ?? fallbackType,
        },
      ]);
    } catch (err) {
      showError('Could not attach', err instanceof Error ? err.message : 'Try again.');
    }
  };

  const attachDocument = async () => {
    try {
      const DocumentPicker = await import('expo-document-picker');
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        copyToCacheDirectory: true,
        multiple: false,
      });
      if (result.canceled || !result.assets?.[0]) return;
      const asset = result.assets[0];
      setFiles((prev) => [
        ...prev,
        {
          kind: 'document',
          uri: asset.uri,
          name: asset.name ?? 'document',
          type: asset.mimeType ?? 'application/octet-stream',
        },
      ]);
    } catch (err) {
      showError('Could not attach', err instanceof Error ? err.message : 'Try again.');
    }
  };

  const addLink = () => {
    const url = linkUrl.trim();
    if (!url) {
      showError('Missing URL', 'Enter a link URL to add.');
      return;
    }
    setLinks((prev) => [...prev, { url, label: linkLabel.trim() }]);
    setLinkUrl('');
    setLinkLabel('');
  };

  const submit = async () => {
    if (!title.trim() || !dueDate || !classroomId || !subjectId) {
      showError('Missing fields', 'Title, due date, class, and subject are required.');
      return;
    }
    const parsedScore = maxScore.trim() ? Number(maxScore.trim()) : undefined;
    if (parsedScore != null && (!Number.isFinite(parsedScore) || parsedScore <= 0)) {
      showError('Invalid marks', 'Max score must be a positive number.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        title: title.trim(),
        instructions: instructions.trim() || undefined,
        due_date: dueDate,
        classroom_id: classroomId,
        subject_id: subjectId,
        target_scope: 'class',
        max_score: parsedScore,
        files: files.map(({ uri, name, type }) => ({ uri, name, type })),
        links: links.map((l) => ({ url: l.url, label: l.label || undefined })),
      });
      showSuccess('Created', 'Homework is visible to parents and students.');
      navigation.goBack();
    } catch (err) {
      showError('Create failed', err instanceof Error ? err.message : 'Could not create homework.');
    }
  };

  const iconFor = (kind: LocalFile['kind']) =>
    kind === 'photo' ? 'image-outline' : kind === 'video' ? 'videocam-outline' : 'document-outline';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Create homework" onBack={() => navigation.goBack()} />
        <TextField label="Title" value={title} onChangeText={setTitle} />
        <TextField label="Due date (YYYY-MM-DD)" value={dueDate} onChangeText={setDueDate} />
        <TextField label="Instructions" value={instructions} onChangeText={setInstructions} multiline />
        <TextField
          label="Max score (optional)"
          value={maxScore}
          onChangeText={setMaxScore}
          keyboardType="number-pad"
        />
        <FilterChipRow label="Class">
          {(classesQuery.data ?? []).slice(0, 30).map((c) => (
            <FilterChip
              key={c.id}
              label={c.name}
              active={classroomId === c.id}
              onPress={() => setClassroomId(c.id)}
            />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Subject you teach">
          {!classroomId ? null : (subjectsQuery.data ?? []).slice(0, 40).map((s) => (
            <FilterChip
              key={s.id}
              label={s.name}
              active={subjectId === s.id}
              onPress={() => setSubjectId(s.id)}
            />
          ))}
        </FilterChipRow>
        {!classroomId ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
            Select a class to see the subjects you teach.
          </Text>
        ) : null}

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.md, marginBottom: spacing.sm }}>
          Attachments
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <Button label="Photo" variant="ghost" onPress={() => void attachPhoto('photo')} />
          <Button label="Video" variant="ghost" onPress={() => void attachPhoto('video')} />
          <Button label="Document" variant="ghost" onPress={() => void attachDocument()} />
        </View>

        {files.map((f, index) => (
          <View
            key={`${f.uri}-${index}`}
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              gap: spacing.sm,
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.md,
              padding: spacing.sm,
              marginTop: spacing.sm,
            }}
          >
            <Soft3DIcon name={iconFor(f.kind)} tone="indigo" size={32} />
            <Text style={{ flex: 1, color: palette.textPrimary }} numberOfLines={1}>
              {f.name}
            </Text>
            <Pressable onPress={() => setFiles((prev) => prev.filter((_, i) => i !== index))} hitSlop={8}>
              <Ionicons name="close-circle" size={22} color={colors.danger ?? palette.textMuted} />
            </Pressable>
          </View>
        ))}

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.md, marginBottom: spacing.sm }}>
          Links
        </Text>
        <TextField label="Link URL" value={linkUrl} onChangeText={setLinkUrl} autoCapitalize="none" placeholder="https://…" />
        <TextField label="Label (optional)" value={linkLabel} onChangeText={setLinkLabel} />
        <Button label="Add link" variant="secondary" onPress={addLink} style={{ marginTop: spacing.xs }} />
        {links.map((l, index) => (
          <View
            key={`${l.url}-${index}`}
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              gap: spacing.sm,
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.md,
              padding: spacing.sm,
              marginTop: spacing.sm,
            }}
          >
            <Soft3DIcon name="link-outline" tone="cyan" size={32} />
            <Text style={{ flex: 1, color: palette.textPrimary }} numberOfLines={1}>
              {l.label || l.url}
            </Text>
            <Pressable onPress={() => setLinks((prev) => prev.filter((_, i) => i !== index))} hitSlop={8}>
              <Ionicons name="close-circle" size={22} color={colors.danger ?? palette.textMuted} />
            </Pressable>
          </View>
        ))}

        <Button
          label="Publish homework"
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.lg }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
