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
  const { palette, spacing, fontSizes } = useTheme();

  return (
    <View style={[styles.row, { marginBottom: spacing.md }]}>
      {onBack ? (
        <Pressable onPress={onBack} hitSlop={12} style={{ marginRight: spacing.sm }}>
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
  row: { flexDirection: 'row', alignItems: 'center' },
});
