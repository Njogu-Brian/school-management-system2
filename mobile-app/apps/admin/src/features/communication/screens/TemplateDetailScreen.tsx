import { useCan, useCommunicationTemplate } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'TemplateDetail'>;

export const TemplateDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { templateId } = route.params;
  const canView = useCan('communication.view');
  const { colors, palette, spacing } = useTheme();
  const query = useCommunicationTemplate(templateId, { enabled: canView });

  const template = query.data;

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
      <Pressable
        onPress={() => navigation.navigate('SmsCompose')}
        style={{ marginTop: spacing.lg, padding: 12, alignItems: 'center' }}
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
