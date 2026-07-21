import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
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
  const { palette, colors, spacing, typography } = useTheme();

  if (!items.length) {
    return (
      <EmptyState
        title="No timeline events"
        message="Activity for this application will appear here."
        icon="time-outline"
      />
    );
  }

  return (
    <View style={{ paddingBottom: spacing.xl }}>
      {items.map((item, index) => (
        <View key={item.id} style={styles.row}>
          <View style={styles.rail}>
            <View style={[styles.dot, { backgroundColor: colors.primary }]} />
            {index < items.length - 1 ? (
              <View style={[styles.line, { backgroundColor: palette.borderSubtle }]} />
            ) : null}
          </View>
          <View style={{ flex: 1, paddingBottom: spacing.lg }}>
            <Text
              style={{
                color: palette.textPrimary,
                fontSize: typography.bodyLarge.fontSize,
                fontWeight: typography.title.fontWeight,
              }}
            >
              {item.title}
            </Text>
            {item.occurredOn ? (
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                  marginTop: spacing.xs / 2,
                }}
              >
                {item.occurredOn}
              </Text>
            ) : null}
            {item.description ? (
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.body.fontSize,
                  marginTop: spacing.xs,
                }}
              >
                {item.description}
              </Text>
            ) : null}
          </View>
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
