import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { StaffEmploymentBadge } from '../staff/StaffEmploymentBadge';
import { useTheme } from '../theme/ThemeContext';
import type { Staff360HeaderData } from './types';

export interface Staff360HeaderProps {
  staff: Staff360HeaderData;
}

export const Staff360Header: React.FC<Staff360HeaderProps> = ({ staff }) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <View
      style={[
        styles.card,
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
        <View style={[styles.avatar, styles.avatarPh, { backgroundColor: palette.accent }]}>
          <Ionicons name="person" size={36} color={colors.primary} />
        </View>
      )}
      <View style={styles.meta}>
        <Text style={[styles.name, { color: palette.textPrimary, fontSize: fontSizes.lg }]}>
          {staff.fullName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
          {staff.employeeNumber}
        </Text>
        {staff.orgLabel ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}>
            {staff.orgLabel}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <StaffEmploymentBadge
            status={staff.employmentStatus as 'active' | 'on_leave' | 'terminated' | 'suspended' | null}
          />
          {staff.systemRole ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: fontSizes.xs,
                alignSelf: 'center',
              }}
            >
              {staff.systemRole}
            </Text>
          ) : null}
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  avatar: { width: 72, height: 72, borderRadius: 36, marginRight: 14 },
  avatarPh: { alignItems: 'center', justifyContent: 'center' },
  meta: { flex: 1 },
  name: { fontWeight: '700' },
  badges: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
});
