import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Soft3DIcon, type Soft3DGlyphKey, type Soft3DTone } from '../primitives/AccentIcon';
import { StatusBadge } from '../primitives/StatusBadge';
import { useTheme } from '../theme/ThemeContext';
import type { SemanticTone } from '../theme/tokens';
import { SurfaceCard } from './SurfaceCard';

export interface ListRowCardProps {
  title: string;
  subtitle?: string;
  meta?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  glyph?: Soft3DGlyphKey;
  tone?: Soft3DTone;
  badge?: string;
  badgeTone?: SemanticTone;
  accent?: SemanticTone;
  onPress?: () => void;
  trailing?: React.ReactNode;
}

export const ListRowCard: React.FC<ListRowCardProps> = ({
  title,
  subtitle,
  meta,
  icon,
  glyph,
  tone,
  badge,
  badgeTone = 'brand',
  accent,
  onPress,
  trailing,
}) => {
  const { palette, spacing, typography } = useTheme();

  return (
    <SurfaceCard onPress={onPress} accent={accent}>
      <View style={styles.row}>
        <Soft3DIcon name={icon} glyph={glyph} tone={tone} size={48} />
        <View style={[styles.body, { marginLeft: spacing.md }]}>
          <View style={styles.titleRow}>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '700',
                fontSize: typography.titleSmall.fontSize,
                flex: 1,
              }}
              numberOfLines={2}
            >
              {title}
            </Text>
            {badge ? <StatusBadge label={badge} tone={badgeTone} compact /> : null}
          </View>
          {subtitle ? (
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginTop: 2,
              }}
              numberOfLines={2}
            >
              {subtitle}
            </Text>
          ) : null}
          {meta ? (
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                marginTop: 4,
                fontWeight: '600',
              }}
            >
              {meta}
            </Text>
          ) : null}
        </View>
        {trailing ?? (onPress ? <Ionicons name="chevron-forward" size={18} color={palette.textMuted} /> : null)}
      </View>
    </SurfaceCard>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
  body: { flex: 1 },
  titleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
