import { useAuditTrailDetail } from '@erp/core';
import { AcademicScreenHeader, Button, EmptyState, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<DashboardStackParamList, 'AuditDetail'>;

function formatJsonBlock(value: unknown): string {
  if (value == null) {
    return '—';
  }
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
}

export const AuditDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { auditId } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const detailQuery = useAuditTrailDetail(auditId);

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (detailQuery.isError || !detailQuery.data) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <EmptyState
          title="Record not found"
          message={(detailQuery.error as Error)?.message ?? 'This audit event could not be loaded.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void detailQuery.refetch()}
        />
        <View style={{ paddingHorizontal: spacing.lg }}>
          <Button label="Go back" variant="ghost" onPress={() => navigation.goBack()} />
        </View>
      </ScreenContainer>
    );
  }

  const record = detailQuery.data;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Audit detail" onBack={() => navigation.goBack()} />
        <FinanceFieldSection
          title="Event"
          rows={[
            { label: 'Actor', value: record.user },
            { label: 'Action', value: record.action },
            { label: 'Module', value: record.module },
            { label: 'Target', value: record.target },
            { label: 'Time', value: formatDateTimeLabel(record.timestamp) },
            { label: 'Source', value: record.source },
          ]}
        />
        <Text
          style={{
            fontWeight: typography.titleSmall.fontWeight,
            fontSize: typography.titleSmall.fontSize,
            marginTop: spacing.md,
            color: palette.textPrimary,
          }}
        >
          Before
        </Text>
        <Text
          style={[
            styles.json,
            {
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              lineHeight: typography.caption.lineHeight,
              marginTop: spacing.sm,
              backgroundColor: palette.surfaceMuted,
              borderRadius: radius.md,
              padding: spacing.mdSm,
            },
          ]}
          selectable
        >
          {formatJsonBlock(record.before_values)}
        </Text>
        <Text
          style={{
            fontWeight: typography.titleSmall.fontWeight,
            fontSize: typography.titleSmall.fontSize,
            marginTop: spacing.md,
            color: palette.textPrimary,
          }}
        >
          After
        </Text>
        <Text
          style={[
            styles.json,
            {
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              lineHeight: typography.caption.lineHeight,
              marginTop: spacing.sm,
              backgroundColor: palette.surfaceMuted,
              borderRadius: radius.md,
              padding: spacing.mdSm,
            },
          ]}
          selectable
        >
          {formatJsonBlock(record.after_values)}
        </Text>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  json: { fontFamily: 'monospace' },
});
