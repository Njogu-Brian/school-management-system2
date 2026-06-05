import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { ApplicationStatusBadge } from './ApplicationStatusBadge';
import type { ApplicationListItemData } from './types';

export interface ApplicationListItemProps {
  application: ApplicationListItemData;
  onPress?: () => void;
}

export const ApplicationListItem: React.FC<ApplicationListItemProps> = ({
  application,
  onPress,
}) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();
  const classLine = [application.preferredClassName ?? application.className]
    .filter(Boolean)
    .join(' · ');

  const body = (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
        },
        shadows.sm,
      ]}
    >
      {application.avatarUrl ? (
        <Image source={{ uri: application.avatarUrl }} style={styles.avatar} />
      ) : (
        <View style={[styles.avatar, styles.avatarPlaceholder, { backgroundColor: palette.accent }]}>
          <Ionicons name="document-text-outline" size={22} color={colors.primary} />
        </View>
      )}

      <View style={styles.content}>
        <Text
          style={[styles.name, { color: palette.textPrimary, fontSize: fontSizes.md }]}
          numberOfLines={1}
        >
          {application.fullName}
        </Text>
        {application.applicationDate ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
            Applied {application.applicationDate}
          </Text>
        ) : null}
        {classLine ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }} numberOfLines={1}>
            {classLine}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <ApplicationStatusBadge status={application.applicationStatus} compact />
          {application.waitlistPosition != null ? (
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
              #{application.waitlistPosition} waitlist
            </Text>
          ) : null}
        </View>
      </View>
    </View>
  );

  if (onPress) {
    return <Pressable onPress={onPress}>{body}</Pressable>;
  }
  return body;
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', borderWidth: StyleSheet.hairlineWidth },
  avatar: { width: 48, height: 48, borderRadius: 24, marginRight: 12 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  content: { flex: 1 },
  name: { fontWeight: '700' },
  badges: { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap' },
});
