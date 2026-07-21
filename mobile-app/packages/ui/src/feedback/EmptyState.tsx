import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AccentIcon } from '../primitives/AccentIcon';
import { Button } from '../primitives/Button';
import { useTheme } from '../theme/ThemeContext';

export interface EmptyStateProps {
  title: string;
  message?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  actionLabel?: string;
  onAction?: () => void;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  title,
  message,
  icon = 'file-tray-outline',
  actionLabel,
  onAction,
}) => {
  const { palette, spacing, typography } = useTheme();

  return (
    <View style={[styles.wrap, { padding: spacing.lg }]}>
      <AccentIcon name={icon} tone="blue" size={80} iconSize={36} />
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.title.fontSize,
          fontWeight: typography.title.fontWeight,
          textAlign: 'center',
          marginTop: spacing.md,
        }}
      >
        {title}
      </Text>
      {message ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.body.fontSize,
            textAlign: 'center',
            marginTop: spacing.xs,
            lineHeight: typography.body.lineHeight,
            maxWidth: 280,
          }}
        >
          {message}
        </Text>
      ) : null}
      {actionLabel && onAction ? (
        <View style={{ marginTop: spacing.md, alignSelf: 'stretch', maxWidth: 280 }}>
          <Button label={actionLabel} onPress={onAction} variant="primary" fullWidth />
        </View>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { alignItems: 'center', justifyContent: 'center' },
});
