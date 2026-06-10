import { useCan, useCommunicationTemplates } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'TemplatesList'>;

export const TemplatesListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const [search, setSearch] = useState('');
  const query = useCommunicationTemplates({ enabled: canView });

  const filtered = useMemo(() => {
    const list = query.data ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return list;
    return list.filter(
      (t) =>
        t.title.toLowerCase().includes(q) ||
        (t.code ?? '').toLowerCase().includes(q) ||
        (t.content ?? '').toLowerCase().includes(q),
    );
  }, [query.data, search]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        showFilterTrigger={false}
        hero={
          <View>
            <AcademicScreenHeader title="SMS templates" onBack={() => navigation.goBack()} />
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.xs }}>
              Read-only — template CRUD is managed on the web portal.
            </Text>
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search templates…" />}
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('TemplateDetail', { templateId: item.id })}
            style={({ pressed }) => [
              elevation[1],
              {
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: palette.borderSubtle,
                backgroundColor: palette.surfaceRaised,
                borderRadius: radius.card,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
              {item.title}
            </Text>
            {item.code ? (
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {item.code}
              </Text>
            ) : null}
            <Text
              style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 6 }}
              numberOfLines={2}
            >
              {item.content ?? '—'}
            </Text>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} colors={[colors.primary]} />
        }
        ListEmptyComponent={
          query.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : query.isError ? (
            <ListEmptyState
              title="Could not load templates"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No templates"
              message={search ? 'No templates match your search.' : 'No SMS templates found.'}
              icon="document-text-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
