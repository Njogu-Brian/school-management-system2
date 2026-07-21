import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface AttendanceDayListItemData {
  id: number;
  date: string;
  status: string;
  checkInTime: string | null;
  checkOutTime: string | null;
  source: 'clock' | 'manual';
}

export interface AttendanceDayListItemProps {
  item: AttendanceDayListItemData;
}

export const AttendanceDayListItem: React.FC<AttendanceDayListItemProps> = ({ item }) => {
  const { palette, spacing, typography, radius } = useTheme();

  const timeLabel =
    item.checkInTime || item.checkOutTime
      ? [item.checkInTime, item.checkOutTime].filter(Boolean).join(' – ')
      : null;

  return (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <View style={styles.header}>
        <Text
          style={{
            color: palette.textPrimary,
            fontWeight: '600',
            fontSize: typography.bodyLarge.fontSize,
          }}
        >
          {item.date}
        </Text>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.overline.fontSize,
            fontWeight: '600',
            textTransform: 'capitalize',
          }}
        >
          {item.status.replace('_', ' ')}
        </Text>
      </View>
      {timeLabel ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginTop: 4,
          }}
        >
          {timeLabel}
        </Text>
      ) : null}
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.overline.fontSize,
          marginTop: 4,
        }}
      >
        {item.source === 'clock' ? 'Geofence clock' : 'Manual mark'}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
