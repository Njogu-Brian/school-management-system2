import { useCan, useCbcStrands, useCbcSubstrands } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import { Ionicons } from '@expo/vector-icons';
import React, { useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'CbcStrands'>;

export const CbcStrandsScreen: React.FC<Props> = ({ navigation, route }) => {
  const { learningAreaId, learningAreaName } = route.params;
  const canView = useCan('academics.view');
  const { colors, palette, spacing, radius, typography } = useTheme();
  const [expandedStrandId, setExpandedStrandId] = useState<number | null>(null);

  const strandsQuery = useCbcStrands(learningAreaId, { enabled: canView });
  const substrandsQuery = useCbcSubstrands(expandedStrandId ?? 0, {
    enabled: canView && expandedStrandId != null,
  });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={strandsQuery.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader
            title={learningAreaName ?? 'Learning area'}
            subtitle="Strands & sub-strands"
            onBack={() => navigation.goBack()}
          />
        }
        refreshControl={
          <RefreshControl
            refreshing={strandsQuery.isRefetching}
            onRefresh={() => void strandsQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => {
          const expanded = expandedStrandId === item.id;
          return (
            <View
              style={[
                styles.card,
                {
                  backgroundColor: palette.surfaceRaised,
                  borderColor: expanded ? colors.primary : palette.borderSubtle,
                  borderRadius: radius.lg,
                },
              ]}
            >
              <Pressable
                onPress={() => setExpandedStrandId(expanded ? null : item.id)}
                accessibilityRole="button"
                style={styles.cardHeader}
              >
                <View style={{ flex: 1 }}>
                  <Text
                    style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}
                    numberOfLines={2}
                  >
                    {[item.code, item.name].filter(Boolean).join(' · ')}
                  </Text>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                    {[item.level, `${item.substrands_count} sub-strand${item.substrands_count === 1 ? '' : 's'}`]
                      .filter(Boolean)
                      .join(' · ')}
                  </Text>
                </View>
                <Ionicons
                  name={expanded ? 'chevron-up' : 'chevron-down'}
                  size={18}
                  color={expanded ? colors.primary : palette.textMuted}
                />
              </Pressable>

              {expanded ? (
                <View style={[styles.substrands, { borderTopColor: palette.borderSubtle }]}>
                  {substrandsQuery.isLoading ? (
                    <ActivityIndicator color={colors.primary} style={{ marginVertical: 12 }} />
                  ) : substrandsQuery.isError ? (
                    <Text style={{ color: palette.textSecondary, paddingVertical: 10 }}>
                      Could not load sub-strands.
                    </Text>
                  ) : (substrandsQuery.data ?? []).length === 0 ? (
                    <Text style={{ color: palette.textSecondary, paddingVertical: 10 }}>
                      No sub-strands defined.
                    </Text>
                  ) : (
                    (substrandsQuery.data ?? []).map((sub) => (
                      <Pressable
                        key={sub.id}
                        onPress={() =>
                          navigation.navigate('CbcSubstrand', { substrandId: sub.id, substrandName: sub.name })
                        }
                        accessibilityRole="button"
                        style={({ pressed }) => [styles.substrandRow, { opacity: pressed ? 0.7 : 1 }]}
                      >
                        <View style={{ flex: 1 }}>
                          <Text style={{ color: palette.textPrimary, fontWeight: '600' }} numberOfLines={2}>
                            {sub.name}
                          </Text>
                          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                            {`${sub.competencies_count} competenc${sub.competencies_count === 1 ? 'y' : 'ies'}`}
                            {sub.suggested_lessons ? ` · ${sub.suggested_lessons} lessons` : ''}
                          </Text>
                        </View>
                        <Ionicons name="chevron-forward" size={14} color={palette.textMuted} />
                      </Pressable>
                    ))
                  )}
                </View>
              ) : null}
            </View>
          );
        }}
        ListEmptyComponent={
          strandsQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : strandsQuery.isError ? (
            <ListEmptyState
              title="Could not load strands"
              message={(strandsQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void strandsQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No strands"
              message="No strands defined for this learning area."
              icon="git-branch-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    marginBottom: 10,
    overflow: 'hidden',
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 14,
  },
  substrands: {
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 14,
    paddingBottom: 6,
  },
  substrandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 10,
  },
});
