import { useCan, useCommunicationTemplates } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
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
} from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'TemplatesList'>;

export const TemplatesListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
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
      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <>
            <AcademicScreenHeader title="SMS templates" onBack={() => navigation.goBack()} />
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
              Read-only — template CRUD is managed on the web portal.
            </Text>
            <TextInput
              value={search}
              onChangeText={setSearch}
              placeholder="Search templates"
              placeholderTextColor={palette.textSecondary}
              style={[styles.search, { borderColor: palette.border, color: palette.textPrimary }]}
            />
          </>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('TemplateDetail', { templateId: item.id })}
            style={[styles.row, { borderColor: palette.border }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.title}</Text>
            {item.code ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>{item.code}</Text>
            ) : null}
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 6 }} numberOfLines={2}>
              {item.content ?? '—'}
            </Text>
          </Pressable>
        )}
        refreshControl={
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            colors={[colors.primary]}
          />
        }
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : query.isError ? (
            <Pressable onPress={() => void query.refetch()}>
              <Text style={{ color: colors.error, textAlign: 'center' }}>Retry</Text>
            </Pressable>
          ) : (
            <EmptyState title="No templates" message="No SMS templates found." icon="document-text-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  search: { borderWidth: 1, borderRadius: 8, padding: 12, marginBottom: 12 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
});
