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
  const { palette, spacing, fontSizes } = useTheme();

  return (
    <View style={[styles.wrap, { marginBottom: spacing.md }]}>
      {onBack ? (
        <Pressable onPress={onBack} style={styles.back} accessibilityRole="button">
          <Ionicons name="arrow-back" size={22} color={palette.textPrimary} />
        </Pressable>
      ) : null}
      <View style={{ flex: 1 }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700' }}>
          {title}
        </Text>
        {subtitle ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}>
            {subtitle}
          </Text>
        ) : null}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { flexDirection: 'row', alignItems: 'center' },
  back: { marginRight: 8, padding: 4 },
});
