import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { ApplicationStatusBadge } from '../admissions/ApplicationStatusBadge';
import { useTheme } from '../theme/ThemeContext';
import type { Admissions360HeaderData } from './types';

export interface Admissions360HeaderProps {
  application: Admissions360HeaderData;
}

export const Admissions360Header: React.FC<Admissions360HeaderProps> = ({ application }) => {
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
          gap: spacing.md,
        },
      ]}
    >
      {application.avatarUrl ? (
        <Image
          source={{ uri: application.avatarUrl }}
          style={[styles.avatar, { borderRadius: radius['2xl'] }]}
        />
      ) : (
        <View
          style={[
            styles.avatar,
            styles.avatarPh,
            {
              backgroundColor: `${colors.primary}12`,
              borderRadius: radius.lg,
            },
          ]}
        >
          <Ionicons name="person" size={36} color={colors.primary} />
        </View>
      )}
      <View style={styles.meta}>
        <Text
          style={{
            color: palette.textMain,
            fontSize: typography.title.fontSize,
            lineHeight: typography.title.lineHeight,
            fontWeight: typography.title.fontWeight,
          }}
        >
          {application.fullName}
        </Text>
        <Text
          style={{
            color: palette.textMuted,
            fontSize: typography.caption.fontSize,
            lineHeight: typography.caption.lineHeight,
          }}
        >
          Application #{application.id}
        </Text>
        {application.applicationDate ? (
          <Text
            style={{
              color: palette.textSub,
              fontSize: typography.caption.fontSize,
              lineHeight: typography.caption.lineHeight,
              marginTop: spacing.xs,
            }}
          >
            Applied {application.applicationDate}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <ApplicationStatusBadge status={application.applicationStatus} compact />
          {application.preferredClassName ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                alignSelf: 'center',
              }}
            >
              Preferred: {application.preferredClassName}
            </Text>
          ) : null}
          {application.waitlistPosition != null &&
          application.applicationStatus === 'waitlisted' ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                alignSelf: 'center',
              }}
            >
              Waitlist #{application.waitlistPosition}
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
  avatar: { width: 72, height: 72 },
  avatarPh: { alignItems: 'center', justifyContent: 'center' },
  meta: { flex: 1 },
  badges: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
});
