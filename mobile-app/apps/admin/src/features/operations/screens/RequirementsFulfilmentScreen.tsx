import { useCan, useRequirementsReport } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ScrollView, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'RequirementsFulfilment'>;

export const RequirementsFulfilmentScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { palette, spacing, typography, radius, colors } = useTheme();
  const query = useRequirementsReport(undefined, { enabled: canView });
  const data = query.data;

  if (!canView) {
    return (
      <ScreenContainer>
        <EmptyState title="Access denied" message="You need operations access to open this report." icon="lock-closed-outline" />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Requirements fulfilment"
          subtitle={data?.term?.name ? `${data.term.name}` : 'Who brought what'}
          onBack={() => navigation.goBack()}
        />
        {query.isLoading ? <SkeletonListRows variant="card" /> : null}
        {query.isError ? (
          <EmptyState
            title="Could not load report"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : null}
        {data ? (
          <>
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
              {[
                { label: 'Fully brought', value: data.summary.complete, color: colors.success },
                { label: 'Partial', value: data.summary.partial, color: '#c2410c' },
                { label: 'Nothing', value: data.summary.none, color: colors.error },
              ].map((kpi) => (
                <View
                  key={kpi.label}
                  style={{
                    flex: 1,
                    backgroundColor: palette.surface,
                    borderRadius: radius.md,
                    padding: spacing.sm,
                    borderWidth: 1,
                    borderColor: palette.border,
                  }}
                >
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>{kpi.label}</Text>
                  <Text style={{ color: kpi.color, fontSize: 22, fontWeight: '800' }}>{kpi.value}</Text>
                </View>
              ))}
            </View>
            {(['complete', 'partial', 'none'] as const).map((key) => (
              <View key={key} style={{ marginBottom: spacing.lg }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '800', marginBottom: spacing.sm }}>
                  {key === 'complete' ? 'Fully brought' : key === 'partial' ? 'Partial' : 'Nothing brought'}
                </Text>
                {data[key].length === 0 ? (
                  <Text style={{ color: palette.textMuted }}>None in this group.</Text>
                ) : (
                  data[key].map((learner) => (
                    <View
                      key={learner.student_id}
                      style={{
                        backgroundColor: palette.surface,
                        borderRadius: radius.md,
                        padding: spacing.md,
                        marginBottom: spacing.sm,
                        borderWidth: 1,
                        borderColor: palette.border,
                      }}
                    >
                      <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{learner.name}</Text>
                      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                        {learner.admission_number} · {learner.class_name} · {learner.complete_count}/{learner.total_count}
                      </Text>
                      {learner.brought_items.length > 0 ? (
                        <Text style={{ color: palette.textSecondary, marginTop: 6 }}>
                          Brought: {learner.brought_items.map((i) => `${i.name} (${i.brought}/${i.expected} ${i.unit})`).join(', ')}
                        </Text>
                      ) : null}
                      {learner.outstanding_items.length > 0 ? (
                        <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
                          Expected: {learner.outstanding_items.map((i) => `${i.name} (${i.outstanding} ${i.unit})`).join(', ')}
                        </Text>
                      ) : null}
                    </View>
                  ))
                )}
              </View>
            ))}
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};
