import {
  useClassroomSubjects,
  useClassrooms,
  useCreateHomework,
  useInfiniteStudentList,
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
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

type LocalFile = HomeworkFileInput & { kind: 'photo' | 'video' | 'document' };
type LocalLink = { url: string; label: string };
type TargetMode = 'class' | 'students';
type Step = 'form' | 'review';

export const CreateAssignmentScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography, colors, radius } = useTheme();
  const classesQuery = useClassrooms();
  const createMutation = useCreateHomework();

  const [title, setTitle] = useState('');
  const [instructions, setInstructions] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [maxScore, setMaxScore] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const [targetMode, setTargetMode] = useState<TargetMode>('class');
  const [selectedStudentIds, setSelectedStudentIds] = useState<number[]>([]);
  const [studentSearch, setStudentSearch] = useState('');
  const [step, setStep] = useState<Step>('form');
  const subjectsQuery = useClassroomSubjects(classroomId);
  const studentsQuery = useInfiniteStudentList({
    search: studentSearch,
    classroomId,
    streamId: null,
    status: 'active',
    perPage: 80,
  });

  const [files, setFiles] = useState<LocalFile[]>([]);
  const [links, setLinks] = useState<LocalLink[]>([]);
  const [linkUrl, setLinkUrl] = useState('');
  const [linkLabel, setLinkLabel] = useState('');

  const students = useMemo(
    () => studentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [studentsQuery.data],
  );

  useEffect(() => {
    setSubjectId(null);
    setSelectedStudentIds([]);
  }, [classroomId]);

  const toggleStudent = (id: number) => {
    setSelectedStudentIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

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
        type: [
          'application/pdf',
          'image/*',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
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

  const validateForm = (): string | null => {
    if (!title.trim() || !dueDate || !classroomId || !subjectId) {
      return 'Title, due date, class, and subject are required.';
    }
    if (targetMode === 'students' && selectedStudentIds.length === 0) {
      return 'Select at least one student, or choose entire class.';
    }
    const parsedScore = maxScore.trim() ? Number(maxScore.trim()) : undefined;
    if (parsedScore != null && (!Number.isFinite(parsedScore) || parsedScore <= 0)) {
      return 'Max score must be a positive number.';
    }
    return null;
  };

  const submit = async () => {
    const errMsg = validateForm();
    if (errMsg) {
      showError('Cannot publish', errMsg);
      return;
    }
    const parsedScore = maxScore.trim() ? Number(maxScore.trim()) : undefined;
    try {
      await createMutation.mutateAsync({
        title: title.trim(),
        instructions: instructions.trim() || undefined,
        due_date: dueDate,
        classroom_id: classroomId!,
        subject_id: subjectId!,
        target_scope: targetMode === 'students' ? 'students' : 'class',
        student_ids: targetMode === 'students' ? selectedStudentIds : undefined,
        max_score: parsedScore,
        files: files.map(({ uri, name, type }) => ({ uri, name, type })),
        links: links.map((l) => ({ url: l.url, label: l.label || undefined })),
      });
      showSuccess('Created', 'Homework notifications will reach only the targeted families.');
      navigation.goBack();
    } catch (err) {
      showError('Create failed', err instanceof Error ? err.message : 'Could not create homework.');
    }
  };

  const iconFor = (kind: LocalFile['kind']) =>
    kind === 'photo' ? 'image-outline' : kind === 'video' ? 'videocam-outline' : 'document-outline';

  const className = (classesQuery.data ?? []).find((c) => c.id === classroomId)?.name ?? '—';
  const subjectName = (subjectsQuery.data ?? []).find((s) => s.id === subjectId)?.name ?? '—';

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Create homework" onBack={() => navigation.goBack()} />

        {step === 'review' ? (
          <View style={{ gap: spacing.md }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Review</Text>
            <Text style={{ color: palette.textSecondary }}>Title: {title.trim()}</Text>
            <Text style={{ color: palette.textSecondary }}>Due: {dueDate}</Text>
            <Text style={{ color: palette.textSecondary }}>
              Class: {className} · Subject: {subjectName}
            </Text>
            <Text style={{ color: palette.textSecondary }}>
              Target:{' '}
              {targetMode === 'class'
                ? 'Entire class'
                : `${selectedStudentIds.length} selected student(s)`}
            </Text>
            <Button label="Publish" loading={createMutation.isPending} onPress={() => void submit()} />
            <Button label="Edit" variant="secondary" onPress={() => setStep('form')} disabled={createMutation.isPending} />
            {createMutation.isError ? (
              <Button label="Retry" variant="ghost" onPress={() => void submit()} />
            ) : null}
          </View>
        ) : (
          <>
            <TextField label="Title" value={title} onChangeText={setTitle} />
            <TextField label="Due date (YYYY-MM-DD)" value={dueDate} onChangeText={setDueDate} />
            <TextField label="Instructions" value={instructions} onChangeText={setInstructions} multiline />
            <TextField
              label="Max score (optional)"
              value={maxScore}
              onChangeText={setMaxScore}
              keyboardType="number-pad"
            />
            <FilterChipRow label="Class" wrap>
              {(classesQuery.data ?? []).map((c) => (
                <FilterChip
                  key={c.id}
                  label={c.name}
                  active={classroomId === c.id}
                  onPress={() => setClassroomId(c.id)}
                />
              ))}
            </FilterChipRow>
            {classesQuery.isLoading ? (
              <Text style={{ color: palette.textMuted, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
                Loading classes…
              </Text>
            ) : null}
            {classesQuery.isError || ((classesQuery.data ?? []).length === 0 && !classesQuery.isLoading) ? (
              <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
                No classes available. Pull to refresh, or confirm you are assigned to a class.
              </Text>
            ) : null}
            <FilterChipRow label="Subject you teach" wrap>
              {!classroomId
                ? null
                : (subjectsQuery.data ?? []).map((s) => (
                    <FilterChip
                      key={s.id}
                      label={s.name}
                      active={subjectId === s.id}
                      onPress={() => setSubjectId(s.id)}
                    />
                  ))}
            </FilterChipRow>

            <FilterChipRow label="Target students">
              <FilterChip
                label="Entire class"
                active={targetMode === 'class'}
                onPress={() => setTargetMode('class')}
              />
              <FilterChip
                label="Specific students"
                active={targetMode === 'students'}
                onPress={() => setTargetMode('students')}
              />
            </FilterChipRow>

            {targetMode === 'students' && classroomId ? (
              <View style={{ marginBottom: spacing.md }}>
                <TextField
                  label="Search students"
                  value={studentSearch}
                  onChangeText={setStudentSearch}
                  placeholder="Name or admission no."
                />
                <Text style={{ color: palette.textMuted, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
                  Selected: {selectedStudentIds.length}
                </Text>
                {studentsQuery.isLoading ? (
                  <Text style={{ color: palette.textMuted }}>Loading students…</Text>
                ) : students.length === 0 ? (
                  <Text style={{ color: palette.textMuted }}>No students in this class.</Text>
                ) : (
                  students.map((s) => {
                    const active = selectedStudentIds.includes(s.id);
                    return (
                      <Pressable
                        key={s.id}
                        onPress={() => toggleStudent(s.id)}
                        style={{
                          padding: spacing.sm,
                          borderRadius: radius.md,
                          borderWidth: 1,
                          borderColor: active ? colors.primary : palette.border,
                          backgroundColor: active ? `${colors.primary}14` : palette.surface,
                          marginBottom: spacing.xs,
                          minHeight: 44,
                        }}
                      >
                        <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                          {s.admissionNumber}
                        </Text>
                      </Pressable>
                    );
                  })
                )}
              </View>
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
              label="Review"
              onPress={() => {
                const msg = validateForm();
                if (msg) {
                  showError('Cannot continue', msg);
                  return;
                }
                setStep('review');
              }}
              style={{ marginTop: spacing.lg }}
            />
          </>
        )}
    </ScreenContainer>
  );
};
