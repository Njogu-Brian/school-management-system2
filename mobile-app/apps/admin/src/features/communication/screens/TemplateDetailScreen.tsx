import { useCan, useCommunicationTemplate, useDeleteTemplate } from '@erp/core';
import { AcademicScreenHeader, Button, EmptyState, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<CommunicationStackParamList, 'TemplateDetail'>;

export const TemplateDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { templateId } = route.params;
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
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
        <EmptyState
          title="Access denied"
          message="You need communication.view permission to view this template."
          icon="lock-closed-outline"
        />
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

  if (query.isError || !template) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Template" onBack={() => navigation.goBack()} />
        <EmptyState
          title="Template not found"
          message={(query.error as Error)?.message ?? 'This template could not be loaded.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title={template.title} onBack={() => navigation.goBack()} />
      <FinanceFieldSection
        title="Template"
        rows={[
          { label: 'Code', value: template.code ?? '—' },
          { label: 'Type', value: template.type },
        ]}
      />
      <View
        style={[
          styles.body,
          {
            borderColor: palette.border,
            marginTop: spacing.md,
            borderRadius: radius.card,
            padding: spacing.md,
          },
        ]}
      >
        <Text
          style={{
            color: palette.textPrimary,
            lineHeight: typography.body.lineHeight,
            fontSize: typography.body.fontSize,
          }}
        >
          {template.content ?? '—'}
        </Text>
      </View>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.caption.fontSize,
          marginTop: spacing.md,
        }}
      >
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
        style={{ marginTop: spacing.sm, padding: spacing.mdSm, alignItems: 'center' }}
      >
        <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.button.fontSize }}>
          Use in SMS compose
        </Text>
      </Pressable>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  body: { borderWidth: StyleSheet.hairlineWidth },
});
