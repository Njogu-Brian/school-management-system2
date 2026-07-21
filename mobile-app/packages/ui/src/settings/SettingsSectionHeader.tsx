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
  const { palette, spacing, typography } = useTheme();

  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.title.fontSize,
          lineHeight: typography.title.lineHeight,
          fontWeight: typography.title.fontWeight,
        }}
      >
        {title}
      </Text>
      {subtitle ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.body.fontSize,
            lineHeight: typography.body.lineHeight,
            marginTop: spacing.xs,
          }}
        >
          {subtitle}
        </Text>
      ) : null}
    </View>
  );
};
