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
  const { palette, spacing, fontSizes, radius } = useTheme();

  const timeLabel =
    item.checkInTime || item.checkOutTime
      ? [item.checkInTime, item.checkOutTime].filter(Boolean).join(' – ')
      : null;

  return (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.md,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <View style={styles.header}>
        <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.md }}>
          {item.date}
        </Text>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: fontSizes.xs,
            fontWeight: '600',
            textTransform: 'capitalize',
          }}
        >
          {item.status.replace('_', ' ')}
        </Text>
      </View>
      {timeLabel ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 4 }}>
          {timeLabel}
        </Text>
      ) : null}
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
        {item.source === 'clock' ? 'Geofence clock' : 'Manual mark'}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
