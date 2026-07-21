import { useInfiniteAuditTrail } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, SkeletonListRows, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<DashboardStackParamList, 'ActivityCenter'>;

const MODULE_FILTERS = ['all', 'Finance', 'Admissions', 'Students', 'HR', 'Visitors', 'Communication', 'Approvals', 'Security'];

export const ActivityCenterScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const [search, setSearch] = useState('');
  const [moduleFilter, setModuleFilter] = useState('all');

  const auditQuery = useInfiniteAuditTrail({
    search: search.trim() || undefined,
    module: moduleFilter === 'all' ? undefined : moduleFilter,
  });

  const rows = useMemo(
    () => auditQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [auditQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={rows}
        keyExtractor={(item) => item.id}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="Audit trail" onBack={() => navigation.goBack()} />
            <TextInput
              value={search}
              onChangeText={setSearch}
              placeholder="Search actions"
              placeholderTextColor={palette.textSecondary}
              style={[
                styles.input,
                {
                  borderColor: palette.borderSubtle,
                  color: palette.textPrimary,
                  borderRadius: radius.control,
                  padding: spacing.mdSm,
                  marginBottom: spacing.sm,
                  fontSize: typography.body.fontSize,
                  backgroundColor: palette.surfaceRaised,
                },
              ]}
            />
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs + 2, marginBottom: spacing.sm }}>
              {MODULE_FILTERS.map((m) => (
                <Pressable
                  key={m}
                  onPress={() => setModuleFilter(m)}
                  style={[
                    styles.chip,
                    {
                      borderColor: moduleFilter === m ? colors.primary : palette.borderSubtle,
                      borderRadius: radius.chip,
                      paddingHorizontal: spacing.sm,
                      paddingVertical: spacing.xs,
                      backgroundColor: moduleFilter === m ? `${colors.primary}14` : palette.surfaceRaised,
                    },
                  ]}
                >
                  <Text
                    style={{
                      fontSize: typography.caption.fontSize,
                      color: moduleFilter === m ? colors.primary : palette.textSecondary,
                      fontWeight: moduleFilter === m ? '600' : '500',
                    }}
                  >
                    {m === 'all' ? 'All' : m}
                  </Text>
                </Pressable>
              ))}
            </View>
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('AuditDetail', { auditId: item.id })}
            style={[
              elevation[1],
              styles.row,
              {
                borderColor: palette.borderSubtle,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                marginBottom: spacing.sm,
              },
            ]}
          >
            <Text
              style={{
                fontWeight: typography.titleSmall.fontWeight,
                color: palette.textPrimary,
                fontSize: typography.titleSmall.fontSize,
              }}
            >
              {item.action}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs,
              }}
              numberOfLines={2}
            >
              {item.target}
            </Text>
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.xs,
              }}
            >
              {[item.user, item.module, formatDateTimeLabel(item.timestamp)].filter(Boolean).join(' · ')}
            </Text>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={auditQuery.isRefetching && !auditQuery.isFetchingNextPage}
            onRefresh={() => void auditQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (auditQuery.hasNextPage && !auditQuery.isFetchingNextPage) {
            void auditQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={auditQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
        ListEmptyComponent={
          auditQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={6} />
          ) : auditQuery.isError ? (
            <EmptyState
              title="Could not load activity"
              message={(auditQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void auditQuery.refetch()}
            />
          ) : (
            <EmptyState title="No activity" message="Audit events will appear here." icon="time-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  input: { borderWidth: 1 },
  chip: { borderWidth: 1 },
  row: { borderWidth: StyleSheet.hairlineWidth },
});
