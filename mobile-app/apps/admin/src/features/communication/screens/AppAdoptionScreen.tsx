import { appAdoptionApi, type AppAdoptionAudience, type AppAdoptionStatus } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import { useQuery } from '@tanstack/react-query';
import React, { useState } from 'react';
import { FlatList, RefreshControl, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'AppAdoption'>;

export const AppAdoptionScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, colors } = useTheme();
  const [audience, setAudience] = useState<AppAdoptionAudience>('staff');
  const [status, setStatus] = useState<AppAdoptionStatus>('all');

  const query = useQuery({
    queryKey: ['admin', 'app-adoption', audience, status],
    queryFn: async () => {
      const res = await appAdoptionApi.list({ audience, status, days: 7, per_page: 50 });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load.');
      return res.data;
    },
  });

  const summary = query.data?.summary;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data?.items ?? []}
        keyExtractor={(item) => String(item.user_id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={colors.primary} />
        }
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader
              title="App adoption"
              subtitle="Who has signed in vs not yet"
              onBack={() => navigation.goBack()}
            />
            {summary ? (
              <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
                Total {summary.total} · Never {summary.never} · Used {summary.used} · Active (7d) {summary.active}
              </Text>
            ) : null}
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.xs }}>
              Audience
            </Text>
            <FilterChipRow>
              <FilterChip label="Staff / teachers" active={audience === 'staff'} onPress={() => setAudience('staff')} />
              <FilterChip label="Parents" active={audience === 'parents'} onPress={() => setAudience('parents')} />
            </FilterChipRow>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: spacing.sm,
                marginBottom: spacing.xs,
              }}
            >
              Status
            </Text>
            <FilterChipRow>
              <FilterChip label="All" active={status === 'all'} onPress={() => setStatus('all')} />
              <FilterChip label="Never signed in" active={status === 'never'} onPress={() => setStatus('never')} />
              <FilterChip label="Has used app" active={status === 'used'} onPress={() => setStatus('used')} />
              <FilterChip label="Active 7d" active={status === 'active'} onPress={() => setStatus('active')} />
            </FilterChipRow>
            <View style={{ height: spacing.md }} />
          </View>
        }
        renderItem={({ item }) => (
          <View
            style={{
              marginBottom: spacing.sm,
              padding: spacing.md,
              borderWidth: 1,
              borderColor: palette.border,
              borderRadius: 12,
              backgroundColor: palette.surface,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {item.email ?? '—'}
              {item.employee_number ? ` · ${item.employee_number}` : ''}
            </Text>
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
              Login: {item.last_login_at ? new Date(item.last_login_at).toLocaleString() : 'Never'}
              {' · '}
              Seen: {item.last_seen_at ? new Date(item.last_seen_at).toLocaleString() : '—'}
              {item.has_active_token ? ' · Signed in now' : ''}
            </Text>
          </View>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <SkeletonListRows count={6} />
          ) : (
            <EmptyState title="No users" message="No accounts match these filters." icon="people-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};
