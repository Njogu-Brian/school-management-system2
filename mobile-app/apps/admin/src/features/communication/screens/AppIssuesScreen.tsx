import { appIssuesApi } from '@erp/core';
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

type Props = StackScreenProps<CommunicationStackParamList, 'AppIssues'>;

export const AppIssuesScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, colors } = useTheme();
  const [app, setApp] = useState<'users' | 'admin' | undefined>(undefined);

  const query = useQuery({
    queryKey: ['admin', 'app-issues', app],
    queryFn: async () => {
      const res = await appIssuesApi.list({ app, per_page: 50 });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load.');
      return res.data;
    },
  });

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data?.items ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={colors.primary} />
        }
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader
              title="App crash logs"
              subtitle="Client issues reported from mobile apps"
              onBack={() => navigation.goBack()}
            />
            <FilterChipRow>
              <FilterChip label="All" active={app == null} onPress={() => setApp(undefined)} />
              <FilterChip label="Users app" active={app === 'users'} onPress={() => setApp('users')} />
              <FilterChip label="Admin app" active={app === 'admin'} onPress={() => setApp('admin')} />
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
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }} numberOfLines={2}>
              {item.message}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
              {item.app} · {item.platform ?? '—'} · {item.app_version ?? '—'}
              {item.user_name ? ` · ${item.user_name}` : ''}
            </Text>
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
              {item.created_at ? new Date(item.created_at).toLocaleString() : ''}
            </Text>
            {item.stack ? (
              <Text
                style={{ color: palette.textMuted, fontSize: 11, marginTop: spacing.xs }}
                numberOfLines={4}
              >
                {item.stack}
              </Text>
            ) : null}
          </View>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <SkeletonListRows count={6} />
          ) : (
            <EmptyState title="No issues" message="No client crashes have been reported yet." icon="bug-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};
