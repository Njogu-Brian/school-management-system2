import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface AcademicScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
}

export const AcademicScreenHeader: React.FC<AcademicScreenHeaderProps> = ({
  title,
  subtitle,
  onBack,
}) => {
  const { palette, spacing, typography } = useTheme();

  return (
    <View style={[styles.wrap, { marginBottom: spacing.md }]}>
      {onBack ? (
        <Pressable
          onPress={onBack}
          style={styles.back}
          accessibilityRole="button"
          accessibilityLabel="Go back"
        >
          <Ionicons name="arrow-back" size={22} color={palette.textPrimary} />
        </Pressable>
      ) : null}
      <View style={{ flex: 1 }}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.title.fontSize,
            fontWeight: typography.title.fontWeight,
          }}
        >
          {title}
        </Text>
        {subtitle ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: 2,
            }}
          >
            {subtitle}
          </Text>
        ) : null}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { flexDirection: 'row', alignItems: 'center' },
  back: { marginRight: 8, padding: 4, minWidth: 44, minHeight: 44, justifyContent: 'center' },
});
