import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '../theme/ThemeContext';
import { ApplicationStatusBadge } from '../admissions/ApplicationStatusBadge';
import type { Admissions360HeaderData } from './types';

export interface Admissions360HeaderProps {
  application: Admissions360HeaderData;
}

export const Admissions360Header: React.FC<Admissions360HeaderProps> = ({ application }) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

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
      ]}
    >
      <View style={styles.row}>
        {application.avatarUrl ? (
          <Image source={{ uri: application.avatarUrl }} style={styles.avatar} />
        ) : (
          <View style={[styles.avatar, { backgroundColor: palette.accent, alignItems: 'center', justifyContent: 'center' }]}>
            <Ionicons name="person-outline" size={28} color={colors.primary} />
          </View>
        )}
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700' }}>
            {application.fullName}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}>
            Application #{application.id}
          </Text>
          {application.applicationDate ? (
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              Applied {application.applicationDate}
            </Text>
          ) : null}
        </View>
      </View>
      <View style={[styles.meta, { marginTop: spacing.sm, gap: spacing.xs }]}>
        <ApplicationStatusBadge status={application.applicationStatus} />
        {application.preferredClassName ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
            Preferred: {application.preferredClassName}
          </Text>
        ) : null}
        {application.waitlistPosition != null ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
            Waitlist position #{application.waitlistPosition}
          </Text>
        ) : null}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  avatar: { width: 64, height: 64, borderRadius: 32 },
  meta: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
});
