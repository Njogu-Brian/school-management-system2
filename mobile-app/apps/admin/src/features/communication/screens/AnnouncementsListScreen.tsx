import { useAnnouncements } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'AnnouncementsList'>;

export const AnnouncementsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useAnnouncements({ perPage: 50 });

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader title="Announcements" subtitle="GET /announcements" onBack={() => navigation.goBack()} />
        }
        renderItem={({ item }) => (
          <View style={[styles.card, { borderColor: palette.border, padding: spacing.md, marginBottom: spacing.sm }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.md }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: spacing.xs }}>{item.content}</Text>
            {item.expires_at ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.xs }}>
                Expires {item.expires_at}
              </Text>
            ) : null}
          </View>
        )}
        ListEmptyComponent={
          query.isLoading ? <ActivityIndicator color={colors.primary} /> : <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No announcements.</Text>
        }
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8 },
});
