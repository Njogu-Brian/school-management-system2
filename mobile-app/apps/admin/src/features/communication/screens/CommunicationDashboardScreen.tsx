import { useAnnouncements, useCan } from '@erp/core';
import { QuickAction, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { CommunicationStackParamList } from '../../../navigation/communicationStackTypes';

type Props = StackScreenProps<CommunicationStackParamList, 'CommunicationDashboard'>;

export const CommunicationDashboardScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('communication.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const announcementsQuery = useAnnouncements({ enabled: canView, perPage: 3 });

  const openAnnouncements = useCallback(() => navigation.navigate('AnnouncementsList'), [navigation]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const recent = announcementsQuery.data?.data ?? [];

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={announcementsQuery.isRefetching}
            onRefresh={() => void announcementsQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Communication
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <QuickAction label="Announcements" icon="megaphone-outline" onPress={openAnnouncements} />
          <QuickAction label="Send SMS" icon="chatbubble-outline" onPress={() => navigation.navigate('SmsCompose')} />
        </View>

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Recent announcements
        </Text>
        {recent.length === 0 ? (
          <Text style={{ color: palette.textSecondary }}>No active announcements.</Text>
        ) : (
          recent.map((a) => (
            <View key={a.id} style={{ marginBottom: spacing.sm, padding: spacing.sm, borderWidth: StyleSheet.hairlineWidth, borderColor: palette.border, borderRadius: 8 }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{a.title}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }} numberOfLines={2}>
                {a.content}
              </Text>
            </View>
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
