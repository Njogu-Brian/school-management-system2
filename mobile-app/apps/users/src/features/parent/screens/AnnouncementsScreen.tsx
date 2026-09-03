import { useAnnouncements } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Text, View } from 'react-native';
import { formatDateTime, formatShortDate } from '../utils/format';

export const AnnouncementsScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography } = useTheme();
  const query = useAnnouncements({ perPage: 40 });
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const items = useMemo(() => {
    const page = query.data;
    if (!page) return [];
    if (Array.isArray(page)) return page;
    return page.data ?? [];
  }, [query.data]);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Announcements"
        onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
      />

      {query.isLoading ? (
        <SkeletonListRows count={4} />
      ) : query.isError ? (
        <EmptyState
          title="Could not load announcements"
          message={query.error instanceof Error ? query.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : items.length === 0 ? (
        <EmptyState
          title="No announcements"
          message="School announcements will appear here when published."
          icon="megaphone-outline"
        />
      ) : (
        items.map((item) => {
          const open = expandedId === item.id;
          return (
            <SurfaceCard
              key={item.id}
              onPress={() => setExpandedId(open ? null : item.id)}
              accent="warning"
            >
              <View style={{ flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' }}>
                <Soft3DIcon name="megaphone-outline" glyph="megaphone" size={44} />
                <View style={{ flex: 1 }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {formatDateTime(item.created_at)}
                {item.expires_at ? ` · Expires ${formatShortDate(item.expires_at)}` : ''}
              </Text>
              <Text
                style={{ color: palette.textSecondary, marginTop: spacing.sm }}
                numberOfLines={open ? undefined : 3}
              >
                {item.content}
              </Text>
                </View>
              </View>
            </SurfaceCard>
          );
        })
      )}
    </ScreenContainer>
  );
};
