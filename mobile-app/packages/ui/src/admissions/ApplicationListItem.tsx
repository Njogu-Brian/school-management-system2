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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();
  const classLine = [application.preferredClassName ?? application.className]
    .filter(Boolean)
    .join(' · ');

  const body = (
    <View
      style={[
        styles.row,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      {application.avatarUrl ? (
        <Image source={{ uri: application.avatarUrl }} style={[styles.avatar, { borderRadius: radius.lg }]} />
      ) : (
        <View
          style={[
            styles.avatar,
            styles.avatarPlaceholder,
            { backgroundColor: `${colors.primary}12`, borderRadius: radius.lg },
          ]}
        >
          <Ionicons name="document-text-outline" size={22} color={colors.primary} />
        </View>
      )}

      <View style={styles.content}>
        <Text
          style={[
            styles.name,
            { color: palette.textPrimary, fontSize: typography.body.fontSize, fontWeight: '600' },
          ]}
          numberOfLines={1}
        >
          {application.fullName}
        </Text>
        {application.applicationDate ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            Applied {application.applicationDate}
          </Text>
        ) : null}
        {classLine ? (
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }} numberOfLines={1}>
            {classLine}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <ApplicationStatusBadge status={application.applicationStatus} compact />
          {application.waitlistPosition != null ? (
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
              #{application.waitlistPosition} waitlist
            </Text>
          ) : null}
        </View>
      </View>

      <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
    </View>
  );

  if (onPress) {
    return (
      <Pressable onPress={onPress} style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1 }]}>
        {body}
      </Pressable>
    );
  }
  return body;
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', borderWidth: StyleSheet.hairlineWidth },
  avatar: { width: 48, height: 48, marginRight: 12 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  content: { flex: 1, marginRight: 8 },
  name: {},
  badges: { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap' },
});
