import { useCan, useCommunicationTemplate, useCreateTemplate, useUpdateTemplate } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'TemplateForm'>;

const TYPES = ['sms', 'whatsapp', 'email'] as const;
type TemplateType = (typeof TYPES)[number];

export const TemplateFormScreen: React.FC<Props> = ({ navigation, route }) => {
  const editId = route.params?.templateId;
  const isEdit = editId != null && editId > 0;
  const canEdit = useCan('communication.view');
  const { palette, spacing, typography } = useTheme();
  const detailQuery = useCommunicationTemplate(editId ?? 0, { enabled: isEdit && canEdit });
  const createMutation = useCreateTemplate();
  const updateMutation = useUpdateTemplate();

  const [title, setTitle] = useState('');
  const [code, setCode] = useState('');
  const [type, setType] = useState<TemplateType>('sms');
  const [subject, setSubject] = useState('');
  const [content, setContent] = useState('');

  useEffect(() => {
    if (detailQuery.data) {
      setTitle(detailQuery.data.title);
      setCode(detailQuery.data.code ?? '');
      setType((detailQuery.data.type as TemplateType) || 'sms');
      setSubject(detailQuery.data.subject ?? '');
      setContent(detailQuery.data.content ?? '');
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
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Edit template" onBack={() => navigation.goBack()} />
        <SkeletonListRows count={5} variant="compact" />
      </ScreenContainer>
    );
  }

  const canSubmit = title.trim().length > 0 && content.trim().length > 0;
  const pending = createMutation.isPending || updateMutation.isPending;

  const onSave = async () => {
    if (!canSubmit) {
      showError('Validation', 'Title and content are required.');
      return;
    }
    const payload = {
      title: title.trim(),
      type,
      code: code.trim() || null,
      subject: subject.trim() || null,
      content: content.trim(),
    };
    try {
      if (isEdit && editId) {
        await updateMutation.mutateAsync({ id: editId, ...payload });
        showSuccess('Updated', 'Template saved.', () => navigation.goBack());
      } else {
        await createMutation.mutateAsync(payload);
        showSuccess('Created', 'Template created.', () => navigation.goBack());
      }
    } catch (err) {
      showError('Save failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={isEdit ? 'Edit template' : 'New template'}
        subtitle="Message templates for SMS, WhatsApp & email"
        onBack={() => navigation.goBack()}
      />

      <Text style={[styles.label, { color: palette.textSecondary, marginTop: spacing.sm }]}>CHANNEL</Text>
      <FilterChipRow>
        {TYPES.map((t) => (
          <FilterChip key={t} label={t.toUpperCase()} active={type === t} onPress={() => setType(t)} />
        ))}
      </FilterChipRow>

      <TextField label="Title" value={title} onChangeText={setTitle} placeholder="Template title" />
      <TextField label="Code (optional)" value={code} onChangeText={setCode} placeholder="e.g. FEE_REMINDER" />
      {type === 'email' ? (
        <TextField label="Subject" value={subject} onChangeText={setSubject} placeholder="Email subject" />
      ) : null}
      <TextField
        label="Content"
        value={content}
        onChangeText={setContent}
        placeholder="Message body — placeholders like {{student_name}} are supported"
        multiline
        numberOfLines={8}
        textAlignVertical="top"
      />

      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
        Placeholders such as {'{{parent_name}}'}, {'{{student_name}}'} and {'{{school_name}}'} are
        resolved at send time.
      </Text>

      <View style={{ marginTop: spacing.lg }}>
        <Button
          label={isEdit ? 'Save changes' : 'Create template'}
          onPress={() => void onSave()}
          disabled={!canSubmit || pending}
          loading={pending}
        />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  label: { fontSize: 12, fontWeight: '700', letterSpacing: 0.4, marginBottom: 6 },
});
