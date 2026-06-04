import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { StaffEmploymentBadge } from './StaffEmploymentBadge';
import type { StaffListItemData } from './types';

export interface StaffListItemProps {
  staff: StaffListItemData;
}

export const StaffListItem: React.FC<StaffListItemProps> = ({ staff }) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  const subtitle = [staff.departmentName, staff.jobTitle].filter(Boolean).join(' · ');
  const roleLine = staff.systemRole ? `Role: ${staff.systemRole}` : null;

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
      {staff.avatarUrl ? (
        <Image source={{ uri: staff.avatarUrl }} style={styles.avatar} />
      ) : (
        <View style={[styles.avatar, styles.avatarPlaceholder, { backgroundColor: palette.accent }]}>
          <Ionicons name="person-outline" size={22} color={colors.primary} />
        </View>
      )}

      <View style={styles.content}>
        <Text
          style={[styles.name, { color: palette.textPrimary, fontSize: fontSizes.md }]}
          numberOfLines={1}
        >
          {staff.fullName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
          {staff.employeeNumber || '—'}
        </Text>
        {subtitle ? (
          <Text
            style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}
            numberOfLines={1}
          >
            {subtitle}
          </Text>
        ) : null}
        {roleLine ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
            {roleLine}
          </Text>
        ) : null}
        <View style={{ marginTop: spacing.xs }}>
          <StaffEmploymentBadge status={staff.employmentStatus} compact />
        </View>
      </View>

      <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} />
    </View>
  );

  if (staff.onPress) {
    return (
      <Pressable
        onPress={staff.onPress}
        accessibilityRole="button"
        style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1, marginBottom: spacing.sm }]}
      >
        {body}
      </Pressable>
    );
  }

  return <View style={{ marginBottom: spacing.sm }}>{body}</View>;
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  avatar: { width: 48, height: 48, borderRadius: 24, marginRight: 12 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  content: { flex: 1, marginRight: 8 },
  name: { fontWeight: '700' },
});
