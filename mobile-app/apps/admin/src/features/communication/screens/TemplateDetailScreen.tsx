import { useCan, useCommunicationTemplate, useDeleteTemplate } from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'TemplateDetail'>;

export const TemplateDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { templateId } = route.params;
  const canView = useCan('communication.view');
  const { colors, palette, spacing } = useTheme();
  const query = useCommunicationTemplate(templateId, { enabled: canView });
  const deleteMutation = useDeleteTemplate();

  const template = query.data;

  const onDelete = () => {
    confirmAction('Delete template', 'This template will be permanently removed.', 'Delete', async () => {
      try {
        await deleteMutation.mutateAsync(templateId);
        showSuccess('Deleted', 'Template removed.', () => navigation.goBack());
      } catch (err) {
        showError('Delete failed', (err as Error).message);
      }
    });
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (!template) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Template" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary }}>Template not found.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={template.title} onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Template"
        rows={[
          { label: 'Code', value: template.code ?? '—' },
          { label: 'Type', value: template.type },
        ]}
      />
      <View style={[styles.body, { borderColor: palette.border, marginTop: spacing.md }]}>
        <Text style={{ color: palette.textPrimary, lineHeight: 22 }}>{template.content ?? '—'}</Text>
      </View>
      <Text style={{ color: palette.textSecondary, fontSize: 12, marginTop: spacing.md }}>
        Placeholders like {'{{student_name}}'}, {'{{balance}}'} are resolved when sending from the web portal.
      </Text>
      <View style={{ marginTop: spacing.lg, gap: spacing.sm }}>
        <Button label="Edit template" onPress={() => navigation.navigate('TemplateForm', { templateId })} />
        <Button
          label={deleteMutation.isPending ? 'Deleting…' : 'Delete template'}
          variant="ghost"
          onPress={onDelete}
          disabled={deleteMutation.isPending}
        />
      </View>
      <Pressable
        onPress={() => navigation.navigate('SmsCompose')}
        style={{ marginTop: spacing.sm, padding: 12, alignItems: 'center' }}
      >
        <Text style={{ color: colors.primary, fontWeight: '600' }}>Use in SMS compose</Text>
      </Pressable>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  body: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 16 },
});
