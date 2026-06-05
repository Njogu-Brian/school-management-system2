import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ApplicationTimelineItemData {
  id: string;
  title: string;
  description: string | null;
  occurredOn: string | null;
}

export interface ApplicationTimelineProps {
  items: ApplicationTimelineItemData[];
}

export const ApplicationTimeline: React.FC<ApplicationTimelineProps> = ({ items }) => {
  const { palette, colors, spacing, fontSizes } = useTheme();

  if (!items.length) {
    return (
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, textAlign: 'center', marginTop: spacing.lg }}>
        No timeline events yet.
      </Text>
    );
  }

  return (
    <View style={{ paddingBottom: spacing.xl }}>
      {items.map((item, index) => (
        <View key={item.id} style={styles.row}>
          <View style={styles.rail}>
            <View style={[styles.dot, { backgroundColor: colors.primary }]} />
            {index < items.length - 1 ? (
              <View style={[styles.line, { backgroundColor: palette.border }]} />
            ) : null}
          </View>
          <View style={{ flex: 1, paddingBottom: spacing.lg }}>
            <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700' }}>
              {item.title}
            </Text>
            {item.occurredOn ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                {item.occurredOn}
              </Text>
            ) : null}
            {item.description ? (
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 4 }}>
                {item.description}
              </Text>
            ) : null}
          </View>
          <Ionicons name="ellipse" size={4} color="transparent" />
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row' },
  rail: { width: 24, alignItems: 'center' },
  dot: { width: 10, height: 10, borderRadius: 5, marginTop: 4 },
  line: { width: 2, flex: 1, marginTop: 4 },
});
