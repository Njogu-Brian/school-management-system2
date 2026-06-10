import { useCan, useCbcLearningAreas } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'CbcCurriculum'>;

export const CbcCurriculumScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view');
  const { colors, palette, spacing, radius, typography } = useTheme();
  const query = useCbcLearningAreas({ enabled: canView });

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
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader
            title="CBC curriculum"
            subtitle="Learning areas, strands & competencies"
            onBack={() => navigation.goBack()}
          />
        }
        refreshControl={
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() =>
              navigation.navigate('CbcStrands', { learningAreaId: item.id, learningAreaName: item.name })
            }
            accessibilityRole="button"
            style={({ pressed }) => [
              styles.card,
              {
                backgroundColor: palette.surfaceRaised,
                borderColor: palette.borderSubtle,
                borderRadius: radius.lg,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <View style={styles.cardHeader}>
              <Text
                style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize, flex: 1 }}
                numberOfLines={2}
              >
                {item.name}
              </Text>
              {item.is_core ? <StatusBadge label="Core" tone="brand" /> : null}
            </View>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
              {[
                item.level_category,
                `${item.strands_count} strand${item.strands_count === 1 ? '' : 's'}`,
              ]
                .filter(Boolean)
                .join(' · ')}
            </Text>
            <View style={styles.chevron}>
              <Ionicons name="chevron-forward" size={16} color={palette.textMuted} />
            </View>
          </Pressable>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : query.isError ? (
            <ListEmptyState
              title="Could not load curriculum"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No learning areas"
              message="CBC curriculum has not been configured yet."
              icon="library-outline"
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
    padding: 14,
    marginBottom: 10,
    shadowColor: '#0f172a',
    shadowOpacity: 0.05,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  chevron: { position: 'absolute', right: 12, top: '50%' },
});
