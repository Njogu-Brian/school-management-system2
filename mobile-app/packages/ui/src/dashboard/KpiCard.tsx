import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface KpiCardProps {
  label: string;
  value: string;
  /** Optional trend caption, e.g. "+4.2% vs last week". */
  delta?: string;
  deltaPositive?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  onPress?: () => void;
}

/** Success-state body for a KPI widget (used inside `WidgetShell`). */
export const KpiCard: React.FC<KpiCardProps> = ({
  label,
  value,
  delta,
  deltaPositive,
  icon = 'stats-chart-outline',
  onPress,
}) => {
  const { palette, colors, fontSizes } = useTheme();

  const deltaColor = deltaPositive === false ? colors.error : colors.success;

  const body = (
    <>
      <View style={styles.header}>
        <View style={[styles.iconWrap, { backgroundColor: palette.accent }]}>
          <Ionicons name={icon} size={20} color={colors.primary} />
        </View>
        <Text style={[styles.label, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
          {label}
        </Text>
      </View>
      <Text style={[styles.value, { color: palette.textPrimary, fontSize: fontSizes.xxl }]}>
        {value}
      </Text>
      {delta ? (
        <Text style={[styles.delta, { color: deltaColor, fontSize: fontSizes.xs }]}>{delta}</Text>
      ) : null}
    </>
  );

  if (onPress) {
    return (
      <Pressable onPress={onPress} accessibilityRole="button">
        {body}
      </Pressable>
    );
  }

  return body;
};

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  iconWrap: {
    width: 32,
    height: 32,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
  },
  label: { fontWeight: '600', letterSpacing: 0.4, textTransform: 'uppercase', flex: 1 },
  value: { fontWeight: '700' },
  delta: { marginTop: 4, fontWeight: '500' },
});
