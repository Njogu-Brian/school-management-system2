import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface FinanceScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
}

export const FinanceScreenHeader: React.FC<FinanceScreenHeaderProps> = ({
  title,
  subtitle,
  onBack,
}) => {
  const { palette, spacing, typography } = useTheme();

  return (
    <View style={[styles.row, { marginBottom: spacing.md }]}>
      {onBack ? (
        <Pressable
          onPress={onBack}
          hitSlop={12}
          accessibilityRole="button"
          accessibilityLabel="Go back"
          style={{ marginRight: spacing.sm, minWidth: 44, minHeight: 44, justifyContent: 'center' }}
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
  row: { flexDirection: 'row', alignItems: 'center' },
});
