import React from 'react';
import { Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface SettingsSectionHeaderProps {
  title: string;
  subtitle?: string;
}

export const SettingsSectionHeader: React.FC<SettingsSectionHeaderProps> = ({
  title,
  subtitle,
}) => {
  const { palette, spacing, fontSizes } = useTheme();

  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: fontSizes.lg,
          fontWeight: '700',
        }}
      >
        {title}
      </Text>
      {subtitle ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 4 }}>
          {subtitle}
        </Text>
      ) : null}
    </View>
  );
};
