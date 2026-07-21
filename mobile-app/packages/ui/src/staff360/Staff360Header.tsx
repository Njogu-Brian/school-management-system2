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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View
      style={[
        styles.card,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      {staff.avatarUrl ? (
        <Image source={{ uri: staff.avatarUrl }} style={styles.avatar} />
      ) : (
        <View
          style={[
            styles.avatar,
            styles.avatarPh,
            { backgroundColor: `${colors.primary}12`, borderRadius: radius.lg },
          ]}
        >
          <Ionicons name="person" size={36} color={colors.primary} />
        </View>
      )}
      <View style={styles.meta}>
        <Text
          style={[
            styles.name,
            {
              color: palette.textPrimary,
              fontSize: typography.title.fontSize,
              fontWeight: typography.title.fontWeight,
            },
          ]}
        >
          {staff.fullName}
        </Text>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
          {staff.employeeNumber}
        </Text>
        {staff.orgLabel ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: 2,
            }}
          >
            {staff.orgLabel}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <StaffEmploymentBadge
            status={
              staff.employmentStatus as 'active' | 'on_leave' | 'terminated' | 'suspended' | null
            }
          />
          {staff.systemRole ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.overline.fontSize,
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
  name: {},
  badges: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
});
